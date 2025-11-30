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
                    Forms\Components\Placeholder::make('items_table')
                        ->label('')
                        ->content(function ($record) {
                            if (!$record) {
                                return 'Tidak ada data';
                            }

                            $grouped = $record->getGroupedItemsWithFields();
                            
                            if (empty($grouped)) {
                                return 'Tidak ada item dalam rombongan ini';
                            }

                            $html = '<div class="space-y-8">';

                            foreach ($grouped as $type => $data) {
                                $html .= '<div class="border rounded-lg p-6 bg-white dark:bg-gray-800">';
                                
                                // Header jenis item
                                $html .= '<h3 class="text-xl font-bold text-primary-600 dark:text-primary-400 mb-4">';
                                $html .= '📦 ' . strtoupper($data['label']);
                                $html .= '</h3>';

                                // Loop setiap item
                                foreach ($data['items'] as $item) {
                                    $progress = $item['progress'];
                                    $progressColor = match(true) {
                                        $progress['percentage'] == 100 => 'bg-green-500',
                                        $progress['percentage'] >= 50 => 'bg-yellow-500',
                                        default => 'bg-red-500'
                                    };

                                    // Sub-header nama item
                                    $html .= '<div class="mb-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">';
                                    $html .= '<div class="flex justify-between items-center mb-2">';
                                    $html .= '<h4 class="text-lg font-semibold">Item: ' . htmlspecialchars($item['nama_pekerjaan']) . '</h4>';
                                    $html .= '<span class="text-sm font-medium">';
                                    $html .= 'Progress: ' . $progress['verified'] . '/' . $progress['total'] . ' field (' . $progress['percentage'] . '%)';
                                    $html .= '</span>';
                                    $html .= '</div>';
                                    
                                    // Progress bar
                                    $html .= '<div class="w-full bg-gray-200 rounded-full h-2">';
                                    $html .= '<div class="' . $progressColor . ' h-2 rounded-full transition-all" style="width: ' . $progress['percentage'] . '%"></div>';
                                    $html .= '</div>';
                                    $html .= '</div>';

                                    // Tabel field
                                    $html .= '<div class="overflow-x-auto mb-6">';
                                    $html .= '<table class="w-full border-collapse border border-gray-300 dark:border-gray-600">';
                                    
                                    // Table header
                                    $html .= '<thead>';
                                    $html .= '<tr class="bg-gray-100 dark:bg-gray-700">';
                                    $html .= '<th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold w-16">No</th>';
                                    $html .= '<th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold">Uraian</th>';
                                    $html .= '<th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold">Keterangan</th>';
                                    $html .= '<th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center font-semibold w-48">Verifikasi</th>';
                                    $html .= '</tr>';
                                    $html .= '</thead>';
                                    
                                    // Table body
                                    $html .= '<tbody>';
                                    
                                    $no = 1;
                                    foreach ($item['fields'] as $field) {
                                        $isVerified = $field['is_verified'];
                                        $fieldValue = $field['field_value'];
                                        
                                        // Format nilai
                                        if (in_array($field['field_name'], ['pagu_rup', 'nilai_kontrak', 'total_nilai']) && is_numeric($fieldValue)) {
                                            $fieldValue = 'Rp ' . number_format($fieldValue, 0, ',', '.');
                                        }
                                        
                                        if (str_contains($field['field_name'], 'tanggal') && $fieldValue && $fieldValue !== '-') {
                                            try {
                                                $fieldValue = \Carbon\Carbon::parse($fieldValue)->format('d/m/Y');
                                            } catch (\Exception $e) {
                                                // Keep original
                                            }
                                        }
                                        
                                        $rowClass = $isVerified ? 'bg-green-50 dark:bg-green-900/20' : '';
                                        
                                        $html .= '<tr class="' . $rowClass . '">';
                                        
                                        // Kolom No
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">' . $no . '</td>';
                                        
                                        // Kolom Uraian
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 font-medium">';
                                        $html .= htmlspecialchars($field['field_label']);
                                        $html .= '</td>';
                                        
                                        // Kolom Keterangan
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3">';
                                        $html .= htmlspecialchars($fieldValue ?? '-');
                                        $html .= '</td>';
                                        
                                        // Kolom Verifikasi
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">';
                                        
                                        $checkboxId = 'verify_' . $item['rombongan_item_id'] . '_' . $field['field_name'];
                                        $checked = $isVerified ? 'checked' : '';
                                        
                                        $html .= '<div class="flex items-center justify-center gap-3">';
                                        
                                        // Checkbox
                                        $html .= '<label class="flex items-center cursor-pointer">';
                                        $html .= '<input type="checkbox" ';
                                        $html .= 'id="' . $checkboxId . '" ';
                                        $html .= 'class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" ';
                                        $html .= $checked . ' ';
                                        $html .= 'onchange="handleVerificationChange(' . $item['rombongan_item_id'] . ', \'' . $field['field_name'] . '\', this.checked)"';
                                        $html .= '>';
                                        $html .= '<span class="ml-2 text-sm">Verifikasi</span>';
                                        $html .= '</label>';
                                        
                                        // Badge
                                        if ($isVerified) {
                                            $html .= '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">';
                                            $html .= '✓ Sudah';
                                            $html .= '</span>';
                                        } else {
                                            $html .= '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">';
                                            $html .= '○ Belum';
                                            $html .= '</span>';
                                        }
                                        
                                        $html .= '</div>';
                                        $html .= '</td>';
                                        
                                        $html .= '</tr>';
                                        $no++;
                                    }
                                    
                                    $html .= '</tbody>';
                                    $html .= '</table>';
                                    $html .= '</div>';
                                }
                                
                                $html .= '</div>';
                            }

                            $html .= '</div>';

                            // JavaScript untuk handle checkbox
                            $html .= '<script>
                                function handleVerificationChange(rombonganItemId, fieldName, isChecked) {
                                    fetch("/verifikator/rombongan-verifikators/verify-field", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": document.querySelector("meta[name=\'csrf-token\']").content
                                        },
                                        body: JSON.stringify({
                                            rombongan_item_id: rombonganItemId,
                                            field_name: fieldName,
                                            is_verified: isChecked
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            // Reload halaman untuk update progress
                                            window.location.reload();
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Error:", error);
                                        alert("Gagal menyimpan verifikasi");
                                    });
                                }
                            </script>';

                            return new HtmlString($html);
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

                Tables\Columns\TextColumn::make('tanggal_masuk_verifikator')
                    ->label('Tanggal Masuk')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),

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

            ->defaultSort('tanggal_masuk_verifikator', 'desc');
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