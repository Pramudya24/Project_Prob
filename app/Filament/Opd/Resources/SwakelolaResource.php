<?php

namespace App\Filament\Opd\Resources;

use App\Filament\Opd\Resources\SwakelolaResource\Pages;
use App\Models\Swakelola;
use App\Models\Rombongan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class SwakelolaResource extends Resource
{
    protected static ?string $model = Swakelola::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Pencatatan Swakelola';
    protected static ?int $navigationSort = 6;
    

    public static function getModelLabel(): string
    {
        return 'Data Pencatatan Swakelola';
    }

    protected static ?string $pluralModelLabel = 'Data Pencatatan Swakelola';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_dibuat')
                            ->label('Tanggal dibuat')
                            ->required()
                            ->default(now())
                            ->readOnly()
                            ->dehydrated()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('nama_pekerjaan')
                            ->label('Nama Pekerjaan')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('kode_rup')
                            ->label('Kode RUP')
                            ->required()
                            ->rule('numeric')
                            ->extraInputAttributes([
                                    'pattern' => '[0-9]*',
                                    'inputmode' => 'numeric',
                                    'onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57'
                                ]),

                        Forms\Components\TextInput::make('pagu_rup')
                            ->label('Pagu RUP')
                            ->required()
                            ->rule('numeric')
                            ->extraInputAttributes([
                                    'pattern' => '[0-9]*',
                                    'inputmode' => 'numeric',
                                    'onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57'
                                ])
                            ->prefix('Rp'),

                        Forms\Components\TextInput::make('kode_paket')
                            ->label('Kode Paket')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Detail Pengadaan')
                    ->schema([
                        Forms\Components\Select::make('jenis_pengadaan')
                            ->label('Jenis Pengadaan')
                            ->required()
                            ->options([
                                'Barang' => 'Barang',
                                'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
                                'Jasa Konsultansi' => 'Jasa Konsultansi',
                                'Jasa Lainnya' => 'Jasa Lainnya',
                                'Terintegrasi' => 'Terintegrasi',
                            ])
                            ->native(false),

                        Forms\Components\FileUpload::make('realisasi')
                            ->label('Realisasi')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg'])
                            ->maxSize(5120)
                            ->directory('realisasi')
                            ->downloadable()
                            ->openable()
                            ->helperText('Upload file JPG/PDF (Max: 5MB)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Nilai Kontrak')
                    ->schema([
                        Forms\Components\TextInput::make('nilai_kontrak')
                            ->label('Nilai Kontrak')
                            ->required()
                            ->formatStateUsing(fn ($state) => $state ? (int) $state : null)
                            ->rule('numeric')
                            ->extraInputAttributes([
                                    'pattern' => '[0-9]*',
                                    'inputmode' => 'numeric',
                                    'onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57'
                                ])
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_dibuat')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_pekerjaan')
                    ->label('Nama Pekerjaan')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('kode_rup')
                    ->label('Kode RUP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pagu_rup')
                    ->label('Pagu RUP')
                    ->formatStateUsing(fn($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nilai_kontrak')
                    ->label('Nilai Kontrak')
                    ->formatStateUsing(fn($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // ✅ ACTION TAMBAH KE ROMBONGAN - SAMA SEPERTI PL
                Tables\Actions\Action::make('add_to_rombongan')
                    ->label('Tambahkan ke Rombongan')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('rombongan_id')
                            ->label('Pilih Rombongan')
                            ->options(Rombongan::all()->pluck('nama_rombongan', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Swakelola $record, array $data) {
                        $rombonganId = $data['rombongan_id'];

                        // Gunakan method addItem dari Model Rombongan
                        $rombongan = Rombongan::find($rombonganId);
                        $result = $rombongan->addItem('App\Models\Swakelola', $record->id);

                        if ($result) {
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil ditambahkan ke rombongan')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Data sudah ada dalam rombongan')
                                ->warning()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Tambahkan ke Rombongan')
                    ->modalSubmitActionLabel('Tambahkan'),

                Tables\Actions\DeleteAction::make(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_pengadaan')
                    ->label('Jenis Pengadaan')
                    ->options([
                        'Barang' => 'Barang',
                        'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
                        'Jasa Konsultansi' => 'Jasa Konsultansi',
                        'Jasa Lainnya' => 'Jasa Lainnya',
                        'Terintegrasi' => 'Terintegrasi',
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    // ✅ BULK ACTION: TAMBAH KE ROMBONGAN - SAMA SEPERTI PL
                    Tables\Actions\BulkAction::make('add_to_rombongan_bulk')
                        ->label('Tambahkan ke Rombongan')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('rombongan_id')
                                ->label('Pilih Rombongan')
                                ->options(Rombongan::all()->pluck('nama_rombongan', 'id'))
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $rombonganId = $data['rombongan_id'];
                            $rombongan = Rombongan::find($rombonganId);
                            $addedCount = 0;

                            foreach ($records as $record) {
                                $result = $rombongan->addItem('App\Models\Swakelola', $record->id);
                                if ($result) {
                                    $addedCount++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("{$addedCount} data berhasil ditambahkan ke rombongan")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('tanggal_dibuat', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSwakelolas::route('/'),
            'create' => Pages\CreateSwakelola::route('/create'),
            'edit' => Pages\EditSwakelola::route('/{record}/edit'),
        ];
    }
}