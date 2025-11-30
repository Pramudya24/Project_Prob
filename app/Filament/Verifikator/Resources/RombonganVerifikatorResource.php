<?php

namespace App\Filament\Verifikator\Resources;

use App\Filament\Verifikator\Resources\RombonganVerifikatorResource\Pages;
use App\Models\Rombongan;
use App\Models\Opd;
use App\Models\RombonganItem;
use App\Models\RombonganItemFieldVerification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class RombonganVerifikatorResource extends Resource
{
    protected static ?string $model = Rombongan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Verifikasi Rombongan';
    protected static ?string $modelLabel = 'Verifikasi Rombongan';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Rombongan')
                ->schema([
                    Forms\Components\TextInput::make('nama_rombongan')
                        ->label('Nama Rombongan')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Placeholder::make('total_items')
                        ->label('Total Item')
                        ->content(fn($record) => $record?->total_items ?? 0),

                    Forms\Components\Placeholder::make('total_nilai')
                        ->label('Total Nilai')
                        ->content(
                            fn($record) =>
                            $record ? 'Rp ' . number_format($record->total_nilai, 0, ',', '.') : 'Rp 0'
                        ),
                ])->columns(3),

            Forms\Components\Section::make('Detail Item dalam Rombongan')
                ->description('Verifikasi setiap field pada item yang dikirim')
                ->schema([
                    Forms\Components\Repeater::make('items_by_type')
                        ->label('')
                        ->schema([
                            Forms\Components\Placeholder::make('type_header')
                                ->label('')
                                ->content(fn($state) => new HtmlString(
                                    '<div class="font-bold text-lg text-primary-600 dark:text-primary-400">' . 
                                    '📦 ' . ($state['type_label'] ?? 'Items') . 
                                    ' <span class="text-sm text-gray-500">(' . 
                                    count($state['items'] ?? []) . ' item)</span></div>'
                                )),

                            Forms\Components\Repeater::make('items')
                                ->schema([
                                    Forms\Components\Grid::make(1)
                                        ->schema([
                                            Forms\Components\Placeholder::make('item_header')
                                                ->label('')
                                                ->content(function ($get) {
                                                    $namaPekerjaan = $get('nama_pekerjaan') ?? 'Item';
                                                    $progress = $get('progress') ?? ['verified' => 0, 'total' => 0, 'percentage' => 0];
                                                    
                                                    $progressColor = match(true) {
                                                        $progress['percentage'] == 100 => 'bg-green-500',
                                                        $progress['percentage'] >= 50 => 'bg-yellow-500',
                                                        default => 'bg-red-500'
                                                    };
                                                    
                                                    return new HtmlString('
                                                        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border">
                                                            <div class="font-semibold text-base mb-2">' . $namaPekerjaan . '</div>
                                                            <div class="flex justify-between items-center text-sm mb-1">
                                                                <span>Progress Verifikasi</span>
                                                                <span class="font-medium">' . $progress['verified'] . '/' . $progress['total'] . ' field (' . $progress['percentage'] . '%)</span>
                                                            </div>
                                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                                <div class="' . $progressColor . ' h-2 rounded-full transition-all" style="width: ' . $progress['percentage'] . '%"></div>
                                                            </div>
                                                        </div>
                                                    ');
                                                }),

                                            Forms\Components\Hidden::make('rombongan_item_id'),
                                            Forms\Components\Hidden::make('item_id'),
                                            Forms\Components\Hidden::make('nama_pekerjaan'),
                                            Forms\Components\Hidden::make('progress'),
                                            Forms\Components\Hidden::make('all_verified'),
                                        ]),

                                    // Tabel Field Verifications
                                    Forms\Components\Repeater::make('fields')
                                        ->label('Detail Field')
                                        ->schema([
                                            Forms\Components\Grid::make(4)
                                                ->schema([
                                                    // Kolom 1: No (auto)
                                                    Forms\Components\Placeholder::make('no')
                                                        ->label('No')
                                                        ->content(function ($get, $livewire) {
                                                            // Get index dari repeater
                                                            $statePath = $get('../../fields');
                                                            return new HtmlString('<div class="font-medium">' . (array_search($get('field_name'), array_column($statePath ?? [], 'field_name')) + 1) . '</div>');
                                                        }),

                                                    // Kolom 2: Uraian (Field Label)
                                                    Forms\Components\Placeholder::make('field_label')
                                                        ->label('Uraian')
                                                        ->content(fn($get) => new HtmlString(
                                                            '<div class="font-medium">' . ($get('field_label') ?? '-') . '</div>'
                                                        )),

                                                    // Kolom 3: Keterangan (Field Value)
                                                    Forms\Components\Placeholder::make('field_value')
                                                        ->label('Keterangan')
                                                        ->content(function ($get) {
                                                            $value = $get('field_value');
                                                            $fieldName = $get('field_name');
                                                            
                                                            // Format nilai
                                                            if (in_array($fieldName, ['pagu_rup', 'nilai_kontrak', 'total_nilai']) && is_numeric($value)) {
                                                                $value = 'Rp ' . number_format($value, 0, ',', '.');
                                                            }
                                                            
                                                            if (str_contains($fieldName, 'tanggal') && $value && $value !== '-') {
                                                                try {
                                                                    $value = \Carbon\Carbon::parse($value)->format('d/m/Y');
                                                                } catch (\Exception $e) {
                                                                    // Keep original value
                                                                }
                                                            }
                                                            
                                                            return new HtmlString('<div class="text-sm">' . ($value ?? '-') . '</div>');
                                                        }),

                                                    // Kolom 4: Status Verifikasi (Checkbox + Badge)
                                                    Forms\Components\Grid::make(1)
                                                        ->schema([
                                                            Forms\Components\Checkbox::make('is_verified')
                                                                ->label('Verifikasi')
                                                                ->inline()
                                                                ->afterStateUpdated(function ($state, $get, $set) {
                                                                    $verificationId = $get('verification_id');
                                                                    $fieldName = $get('field_name');
                                                                    $rombonganItemId = $get('../../../rombongan_item_id');

                                                                    if ($rombonganItemId && $fieldName) {
                                                                        $rombonganItem = RombonganItem::find($rombonganItemId);
                                                                        
                                                                        if ($rombonganItem) {
                                                                            $verification = $rombonganItem->getOrCreateFieldVerification($fieldName);
                                                                            
                                                                            $verification->update([
                                                                                'is_verified' => $state,
                                                                                'verified_at' => $state ? now() : null,
                                                                                'verified_by' => $state ? auth()->id() : null,
                                                                            ]);

                                                                            $set('verification_id', $verification->id);
                                                                        }
                                                                    }
                                                                })
                                                                ->reactive()
                                                                ->live(),

                                                            Forms\Components\Placeholder::make('status_badge')
                                                                ->label('')
                                                                ->content(function ($get) {
                                                                    $isVerified = $get('is_verified');
                                                                    
                                                                    if ($isVerified) {
                                                                        return new HtmlString('
                                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                                                ✓ Sudah
                                                                            </span>
                                                                        ');
                                                                    } else {
                                                                        return new HtmlString('
                                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                                                ○ Belum
                                                                            </span>
                                                                        ');
                                                                    }
                                                                }),

                                                            Forms\Components\Hidden::make('field_name'),
                                                            Forms\Components\Hidden::make('verification_id'),
                                                        ]),
                                                ]),
                                        ])
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->defaultItems(0)
                                        ->columns(1),
                                ])
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => 
                                    ($state['nama_pekerjaan'] ?? 'Item') . 
                                    ' - ' . 
                                    ($state['progress']['verified'] ?? 0) . '/' . 
                                    ($state['progress']['total'] ?? 0) . ' field' .
                                    ($state['all_verified'] ? ' ✓' : '')
                                )
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->defaultItems(0),
                        ])
                        ->collapsible()
                        ->collapsed()
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->defaultItems(0)
                        ->afterStateHydrated(function ($state, $set, $record) {
                            if ($record) {
                                $grouped = $record->getGroupedItemsWithFields();
                                $formatted = [];
                                
                                foreach ($grouped as $type => $data) {
                                    $formatted[] = [
                                        'type_label' => $data['label'],
                                        'items' => $data['items'],
                                    ];
                                }
                                
                                $set('items_by_type', $formatted);
                            }
                        }),
                ]),

            Forms\Components\Section::make('Verifikasi')
                ->schema([
                    Forms\Components\Placeholder::make('validasi_otomatis')
                        ->label('Status Validasi Otomatis')
                        ->content(function ($record) {
                            if (!$record) return '-';

                            $isValid = $record->checkAutoValidation();
                            $progress = $record->getVerificationProgress();

                            $progressBar = '
                                <div class="mb-3">
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm font-medium">Progress Verifikasi Keseluruhan</span>
                                        <span class="text-sm font-medium">' . $progress['verified'] . '/' . $progress['total'] . ' field (' . $progress['percentage'] . '%)</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                                        <div class="bg-blue-600 h-3 rounded-full transition-all" style="width: ' . $progress['percentage'] . '%"></div>
                                    </div>
                                </div>';

                            $badge = $isValid 
                                ? '<span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">✓ Lolos Verif - Semua field sudah diverifikasi</span>'
                                : '<span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">✗ Belum Lolos - Ada field yang belum diverifikasi</span>';

                            return new HtmlString($progressBar . $badge);
                        }),

                    Forms\Components\Select::make('status_verifikasi')
                        ->label('Status Verifikasi')
                        ->options([
                            'Belum' => 'Belum',
                            'Sudah' => 'Sudah',
                        ])
                        ->required()
                        ->default('Belum'),

                    Forms\Components\Textarea::make('keterangan_verifikasi')
                        ->label('Keterangan Keseluruhan')
                        ->rows(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_rombongan')
                    ->label('Nama Rombongan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_opd')
                    ->label('OPD')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Belum' => 'warning',
                        'Sudah' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('verification_progress')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        $progress = $record->getVerificationProgress();
                        return $progress['verified'] . '/' . $progress['total'] . ' (' . $progress['percentage'] . '%)';
                    })
                    ->badge()
                    ->color(fn($record) => 
                        $record->getVerificationProgress()['percentage'] === 100 ? 'success' : 'warning'
                    ),

                Tables\Columns\TextColumn::make('status_pengiriman')
                    ->label('Status Pengiriman')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Belum Dikirim' => 'gray',
                        'Terkirim ke Verifikator' => 'info',
                        'Revisi' => 'warning',
                        'Dikirim ke SPM' => 'success',
                        default => 'gray',
                    }),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('nama_opd')
                    ->label('Pilih OPD')
                    ->options(
                        Opd::orderBy('code')
                            ->pluck('code', 'code')
                    )
                    ->searchable(),

                Tables\Filters\SelectFilter::make('status_verifikasi')
                    ->options([
                        'Belum' => 'Belum',
                        'Sudah' => 'Sudah',
                    ]),

                Tables\Filters\SelectFilter::make('status_pengiriman')
                    ->options([
                        'Belum Dikirim' => 'Belum Dikirim',
                        'Terkirim ke Verifikator' => 'Terkirim ke Verifikator',
                        'Revisi' => 'Revisi',
                        'Dikirim ke SPM' => 'Dikirim ke SPM',
                    ]),
            ])

            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label('Verifikasi'),

                Tables\Actions\Action::make('kirim_ke_spm')
                    ->label('Kirim ke SPM')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(
                        fn($record) =>
                        $record->status_verifikasi === 'Sudah'
                            && $record->checkAutoValidation()
                            && $record->status_pengiriman !== 'Dikirim ke SPM'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Rombongan ke SPM')
                    ->modalDescription('Rombongan ini sudah lolos verifikasi dan akan dikirim ke SPM. Apakah Anda yakin?')
                    ->modalSubmitActionLabel('Ya, Kirim ke SPM')
                    ->action(function ($record) {
                        $record->update([
                            'status_pengiriman' => 'Dikirim ke SPM',
                            'lolos_verif' => true,
                        ]);

                        Notification::make()
                            ->title('Berhasil Dikirim ke SPM')
                            ->body('Rombongan "' . $record->nama_rombongan . '" telah dikirim ke SPM.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('kirim_kembali')
                    ->label('Kirim Kembali')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(
                        fn($record) =>
                        $record->status_verifikasi === 'Sudah'
                            && !$record->checkAutoValidation()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Kembali ke OPD untuk Revisi')
                    ->modalDescription('Rombongan ini akan dikembalikan ke OPD untuk diperbaiki.')
                    ->action(function ($record) {
                        $record->update([
                            'status_verifikasi' => 'Belum',
                            'status_pengiriman' => 'Revisi',
                            'keterangan_verifikasi' => ($record->keterangan_verifikasi ?? '') .
                                "\n\n[Dikembalikan untuk revisi pada " . now()->format('d/m/Y H:i') . "]",
                        ]);

                        Notification::make()
                            ->title('Rombongan dikembalikan ke OPD')
                            ->success()
                            ->send();
                    }),
            ])

            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRombonganVerifikators::route('/'),
            'create' => Pages\CreateRombonganVerifikator::route('/create'),
            'edit'   => Pages\EditRombonganVerifikator::route('/{record}/edit'),
        ];
    }

    // Filter hanya untuk verifikator
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status_pengiriman', '!=', 'Belum Dikirim');
    }
}