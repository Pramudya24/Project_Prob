<?php

namespace App\Filament\Opd\Resources\Pls;

use App\Filament\Opd\Resources\Pls\Pages;
use App\Models\Pl;
use App\Models\Rombongan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;

class PlResource extends Resource
{
    protected static ?string $model = Pl::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Non Tender';

    

    protected static ?int $navigationSort = 3;

    

    public static function getModelLabel(): string
    {
        return 'Data Non Tender';
    }

    protected static ?string $pluralModelLabel = 'Data Non Tender';

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
                                ]), // ✅ HANYA ANGKA

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
                        Forms\Components\Select::make('metode_pengadaan')
                            ->label('Metode Pengadaan')
                            ->options([
                                'EPengadaan Langsung' => 'E-Pengadaan Langsung',
                                'EPenunjukan Langsung' => 'E-Penunjukan Langsung',
                            ])
                            ->native(false)
                            ->required(),

                        Forms\Components\FileUpload::make('summary_report')
                            ->label('Summary Report')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg'])
                            ->maxSize(5120)
                            ->directory('summary-reports')
                            ->downloadable()
                            ->openable()
                            ->helperText('Upload file JPG/PDF (Max: 5MB)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Nilai Kontrak & Komponen')
                    ->schema([
                        Forms\Components\TextInput::make('nilai_kontrak')
                            ->label('Nilai Kontrak')
                            ->step(1)
                            ->rule('numeric')
                            ->formatStateUsing(fn ($state) => $state ? (int) $state : null)
                            ->extraInputAttributes([
                                    'pattern' => '[0-9]*',
                                    'inputmode' => 'numeric',
                                    'onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57'
                                ])
                            ->required()
                            ->live(onBlur: true)
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                $pdnTkdnImpor = $get('pdn_tkdn_impor');
                                $umkNonUmk = $get('umk_non_umk');

                                if ($pdnTkdnImpor === 'IMPOR') {
                                    $set('nilai_pdn_tkdn_impor', 0);
                                } elseif ($pdnTkdnImpor) {
                                    $set('nilai_pdn_tkdn_impor', $state);
                                }

                                if ($umkNonUmk === 'Non UMK') {
                                    $set('nilai_umk', 0);
                                } elseif ($umkNonUmk) {
                                    $set('nilai_umk', $state);
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Fieldset::make('PDN/TKDN/IMPOR')
                                    ->schema([
                                        Forms\Components\Radio::make('pdn_tkdn_impor')
                                            ->label('Pilih salah satu')
                                            ->required()
                                            ->options([
                                                'PDN' => 'PDN',
                                                'TKDN' => 'TKDN',
                                                'IMPOR' => 'IMPOR',
                                            ])
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                                $nilaiKontrak = $get('nilai_kontrak');
                                                if ($state === 'IMPOR') {
                                                    $set('nilai_pdn_tkdn_impor', 0);
                                                    $set('persentase_tkdn', null);
                                                } elseif ($state === 'PDN') {
                                                    $set('nilai_pdn_tkdn_impor', $nilaiKontrak);
                                                    $set('persentase_tkdn', null);
                                                } else {
                                                    $set('nilai_pdn_tkdn_impor', 0);
                                                    $set('persentase_tkdn', 0);
                                                }
                                            })
                                            ->inline()
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpan(1),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('nilai_pdn_tkdn_impor')
                                            ->label('Nilai IMPOR')
                                            ->formatStateUsing(fn ($state) => $state ? (int) $state : null)
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefix('Rp')
                                            ->visible(
                                                fn(Forms\Get $get): bool =>
                                                in_array($get('pdn_tkdn_impor'), ['PDN', 'IMPOR'])
                                            ),

                                        Forms\Components\Grid::make()
                                            ->schema([
                                                Forms\Components\TextInput::make('persentase_tkdn')
                                                    ->label('Persentase TKDN')
                                                    ->rule('numeric')
                                                    ->extraInputAttributes([
                                    'pattern' => '[0-9]*',
                                    'inputmode' => 'numeric',
                                    'onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57'
                                ])
                                                    ->suffix('%')
                                                    ->minValue(0)
                                                    ->maxValue(100)
                                                    ->required(fn(Forms\Get $get): bool => $get('pdn_tkdn_impor') === 'TKDN')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                                        $nilaiKontrak = $get('nilai_kontrak');
                                                        $persentase = $state ?: 0;
                                                        $hasil = $nilaiKontrak * ($persentase / 100);
                                                        $set('nilai_pdn_tkdn_impor', $hasil);
                                                    }),
                                                Forms\Components\TextInput::make('nilai_pdn_tkdn_impor')
                                                    ->label('Nilai TKDN')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->readonly() // Biar tidak bisa diedit
                                                    ->default(0),
                                            ])
                                            ->columns(2)
                                            ->visible(
                                                fn(Forms\Get $get): bool =>
                                                $get('pdn_tkdn_impor') === 'TKDN'
                                            ),
                                    ])
                                    ->columnSpan(1),
                            ])
                            ->columns(2),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Fieldset::make('UMK / Non UMK')
                                    ->schema([
                                        Forms\Components\Radio::make('umk_non_umk')
                                        ->label('Pilih salah satu')
                                            ->required()
                                            ->options([
                                                'UMK' => 'UMK',
                                                'Non UMK' => 'Non UMK',
                                            ])
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                                $nilaiKontrak = $get('nilai_kontrak');
                                                if ($state === 'Non UMK') {
                                                    $set('nilai_umk', 0);
                                                } elseif ($state) {
                                                    $set('nilai_umk', $nilaiKontrak);
                                                }
                                            })
                                            ->inline()
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('nilai_umk')
                                    ->label('Nilai UMK')
                                    ->formatStateUsing(fn ($state) => $state ? (int) $state : null)
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix('Rp')
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('Status Pekerjaan')
                    ->schema([
                        Forms\Components\Select::make('serah_terima_pekerjaan')
                            ->label('Serah Terima Pekerjaan')
                            ->required()
                            ->options([
                                'BAST' => 'BAST',
                                'On Progres' => 'On Progres',
                            ])
                            ->live()
                            ->native(false),

                        Forms\Components\FileUpload::make('bast_document')
                            ->label('Upload BAST')
                            ->required(fn(Forms\Get $get): bool => $get('serah_terima_pekerjaan') === 'BAST')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
                            ->maxSize(5120)
                            ->directory('bast-documents')
                            ->downloadable()
                            ->openable()
                            ->visible(
                                fn(Forms\Get $get): bool =>
                                $get('serah_terima_pekerjaan') === 'BAST'
                            ),

                        Forms\Components\Select::make('penilaian_kinerja')
                            ->label('Penilaian Kinerja')
                            ->required()
                            ->options([
                                'Baik Sekali' => 'Baik Sekali',
                                'Baik' => 'Baik',
                                'Cukup' => 'Cukup',
                                'Buruk' => 'Buruk',
                                'Belum Dinilai' => 'Belum Dinilai',
                            ])
                            ->native(false),
                    ])
                    ->columns(2),
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
                Tables\Actions\Action::make('add_to_rombongan')
                    ->label('Tambahkan ke Rombongan')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('rombongan_id')
                            ->label('Pilih Rombongan')
                            ->options(\App\Models\Rombongan::all()->pluck('nama_rombongan', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Pl $record, array $data) {
                        $rombonganId = $data['rombongan_id'];

                        // Gunakan method addItem dari Model Rombongan
                        $rombongan = \App\Models\Rombongan::find($rombonganId);
                        $result = $rombongan->addItem('App\Models\Pl', $record->id);

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

                    // ✅ Bulk Action: Kirim ke Rombongan
                    // Bulk action - PERBAIKI parameter $records
                    Tables\Actions\BulkAction::make('add_to_rombongan_bulk')
                        ->label('Tambahkan ke Rombongan')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('rombongan_id')
                                ->label('Pilih Rombongan')
                                ->options(\App\Models\Rombongan::all()->pluck('nama_rombongan', 'id'))
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) { // ✅ TAMBAH TYPE HINT
                            $rombonganId = $data['rombongan_id'];
                            $rombongan = \App\Models\Rombongan::find($rombonganId);
                            $addedCount = 0;

                            foreach ($records as $record) {
                                $result = $rombongan->addItem('App\Models\Pl', $record->id);
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
            'index' => Pages\ListPls::route('/'),
            'create' => Pages\CreatePl::route('/create'),
            'edit' => Pages\EditPl::route('/{record}/edit'),
        ];
    }
}