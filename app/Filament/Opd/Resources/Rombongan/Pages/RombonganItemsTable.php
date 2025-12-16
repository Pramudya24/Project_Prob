<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use Log;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\RombonganItem;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\CanPollRecords;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\View\View;

class RombonganItemsTable extends BaseWidget
{

    use InteractsWithTable;
    use InteractsWithForms;

    public int $rombonganId;

    protected static ?string $heading = 'Data dalam Rombongan';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn(): Builder => RombonganItem::where('rombongan_id', $this->rombonganId)
                    ->with(['item'])
            )
            // ->poll('1s') // âœ… TAMBAHKAN INI - Auto refresh setiap 3 detik
            ->columns([
                Tables\Columns\TextColumn::make('item_type')
                    ->label('Jenis Data')
                    ->formatStateUsing(fn($state) => $this->getTypeLabel($state))
                    ->badge()
                    ->color(fn($state) => $this->getTypeColor($state)),

                Tables\Columns\TextColumn::make('item.nama_pekerjaan')
                    ->label('Nama Pekerjaan')
                    ->searchable()
                    ->limit(50)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('item.kode_rup')
                    ->label('Kode RUP')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('item.pagu_rup')
                    ->label('Pagu RUP')
                    ->formatStateUsing(fn($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('item.nilai_kontrak')
                    ->label('Nilai Kontrak')
                    ->formatStateUsing(fn($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit Data')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->modalHeading(fn($record) => 'Edit Data: ' . ($record->item->nama_pekerjaan ?? $this->getTypeLabel($record->item_type)))
                    ->modalSubmitActionLabel('Simpan Perubahan')
                    ->form(function ($record) {
                        return $this->getEditFormSchema($record);
                    })
                    ->action(function ($record, array $data) {
                        $this->updateItemData($record, $data);
                    }),

                Tables\Actions\Action::make('view')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn($record) => 'Detail Data: ' . ($record->item->nama_pekerjaan ?? $this->getTypeLabel($record->item_type)))
                    ->form(function ($record) {
                        return $this->getViewFormSchema($record);
                    }),

                Tables\Actions\Action::make('delete')
                    ->label('Keluarkan')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function ($record) {
                        $record->delete();
                        
                        // âœ… Dispatch event untuk refresh kedua table
                        $this->dispatch('refreshRombonganItems');
                        $this->dispatch('refreshAvailableItems');
                        
                        // âœ… Notifikasi
                        \Filament\Notifications\Notification::make()
                            ->title('Data dikeluarkan')
                            ->body('Data berhasil dikeluarkan dari rombongan')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Keluarkan dari Rombongan')
                    ->modalDescription('Apakah Anda yakin ingin mengeluarkan data ini dari rombongan?')
                    ->modalSubmitActionLabel('Ya,Â Keluarkan'),
            ])
            ->emptyStateHeading('Belum ada data dalam rombongan')
            ->emptyStateDescription('Tambahkan data pekerjaan dari tab "Data Tersedia".') // âœ… UPDATE DESCRIPTION
            ->emptyStateIcon('heroicon-o-document')
            ->emptyStateActions([
            ])
            ->striped();
    }
    protected $listeners = [
    'refreshRombonganItems' => '$refresh',
    'refreshAvailableItems' => '$refresh',
    ];

    private function getEditFormSchema($record): array
    {
        $item = $record->item;

        if (!$item) {
            return [
                Forms\Components\Placeholder::make('error')
                    ->content('Data tidak ditemukan atau telah dihapus.'),
            ];
        }
        // âœ… FORM UMUM UNTUK SEMUA JENIS DATA
        $commonFields = [
            Forms\Components\TextInput::make('nama_pekerjaan')
                ->label('Nama Pekerjaan')
                ->required()
                ->default($item->nama_pekerjaan ?? ''),

            Forms\Components\TextInput::make('kode_rup')
                ->label('Kode RUP')
                ->default($item->kode_rup ?? ''),

            Forms\Components\TextInput::make('pagu_rup')
                ->label('Pagu RUP')
                ->numeric()
                ->default($item->pagu_rup ?? 0),

            Forms\Components\TextInput::make('nilai_kontrak')
                ->label('Nilai Kontrak')
                ->numeric()
                ->required()
                ->default($item->nilai_kontrak ?? 0),
        ];

        // âœ… FIELD TAMBAHAN BERDASARKAN JENIS DATA
        $additionalFields = match ($record->item_type) {
            'App\Models\Pl' => $this->getPlFields($item),
            'App\Models\Tender' => $this->getTenderFields($item),
            'App\Models\Epurcasing' => $this->getEpurcasingFields($item),
            'App\Models\Swakelola' => $this->getSwakelolaFields($item),
            'App\Models\PengadaanDarurat' => $this->getPengadaanDaruratFields($item),
            'App\Models\nontender' => $this->getnontenderFields($item),
            default => []
        };

        return array_merge($commonFields, $additionalFields);
    }

    // âœ… FIELD KHUSUS UNTUK PL - HANYA FIELD TAMBAHAN SAJA
    private function getPlFields($item): array
    {
        return [
            Forms\Components\Section::make('Informasi Dasar PL')
                ->schema([
                    Forms\Components\DatePicker::make('tanggal_dibuat')
                        ->label('Tanggal dibuat')
                        ->required()
                        ->default($item->tanggal_dibuat ?? now())
                        ->native(false)
                        ->displayFormat('d/m/Y'),

                    Forms\Components\TextInput::make('kode_paket')
                        ->label('Kode Paket')
                        ->required()
                        ->maxLength(255)
                        ->default($item->kode_paket ?? ''),
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
                        ->native(false)
                        ->default($item->jenis_pengadaan ?? ''),
                ]),

            Forms\Components\Section::make('Nilai Kontrak & Komponen')
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
                                ->inline()
                                ->default($item->pdn_tkdn_impor ?? ''),

                            Forms\Components\TextInput::make('nilai_pdn_tkdn_impor')
                                ->label('Nilai PDN/TKDN/IMPOR')
                                ->numeric()
                                ->prefix('Rp')
                                ->default($item->nilai_pdn_tkdn_impor ?? 0),

                            Forms\Components\TextInput::make('persentase_tkdn')
                                ->label('Persentase TKDN')
                                ->numeric()
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->default($item->persentase_tkdn ?? 0),
                        ])
                        ->columns(1),

                    Forms\Components\Fieldset::make('UMK / Non UMK')
                        ->schema([
                            Forms\Components\Radio::make('umk_non_umk')
                                ->required()
                                ->options([
                                    'UMK' => 'UMK',
                                    'Non UMK' => 'Non UMK',
                                ])
                                ->inline()
                                ->default($item->umk_non_umk ?? ''),

                            Forms\Components\TextInput::make('nilai_umk')
                                ->label('Nilai UMK')
                                ->numeric()
                                ->prefix('Rp')
                                ->default($item->nilai_umk ?? 0),
                        ])
                        ->columns(1),
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
                        ->native(false)
                        ->default($item->serah_terima_pekerjaan ?? ''),

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
                        ->native(false)
                        ->default($item->penilaian_kinerja ?? ''),
                ])
                ->columns(2),
        ];
    }

    // âœ… FIELD KHUSUS UNTUK TENDER
    private function getTenderFields($item): array
    {
        return [
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
                        ->numeric()
                        ->integer(),

                    Forms\Components\TextInput::make('pagu_rup')
                        ->label('Pagu RUP')
                        ->required()
                        ->numeric()
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
                        ->options([
                            'Barang' => 'Barang',
                            'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
                            'Jasa Konsultansi' => 'Jasa Konsultansi',
                            'Jasa Lainnya' => 'Jasa Lainnya',
                            'Terintegrasi' => 'Terintegrasi',
                        ])
                        ->required()
                        ->native(false),

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
                        ->numeric()
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
                                        ->label('Nilai PDN/TKDN/IMPOR')
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
                                                ->numeric()
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
        ];
    }

    // âœ… FIELD KHUSUS UNTUK E-PURCHASING
    private function getEpurcasingFields($item): array
    {
        return [
            Forms\Components\Section::make('Informasi Dasar PL')
                ->schema([
                    Forms\Components\DatePicker::make('tanggal_dibuat')
                        ->label('Tanggal dibuat')
                        ->required()
                        ->default($item->tanggal_dibuat ?? now())
                        ->native(false)
                        ->displayFormat('d/m/Y'),

                    Forms\Components\TextInput::make('kode_paket')
                        ->label('Kode Paket')
                        ->required()
                        ->maxLength(255)
                        ->default($item->kode_paket ?? ''),
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
                        ->native(false)
                        ->default($item->jenis_pengadaan ?? ''),
                ]),

            Forms\Components\Section::make('Nilai Kontrak & Komponen')
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
                                ->inline()
                                ->default($item->pdn_tkdn_impor ?? ''),

                            Forms\Components\TextInput::make('nilai_pdn_tkdn_impor')
                                ->label('Nilai PDN/TKDN/IMPOR')
                                ->numeric()
                                ->prefix('Rp')
                                ->default($item->nilai_pdn_tkdn_impor ?? 0),

                            Forms\Components\TextInput::make('persentase_tkdn')
                                ->label('Persentase TKDN')
                                ->numeric()
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->default($item->persentase_tkdn ?? 0),
                        ])
                        ->columns(1),

                    Forms\Components\Fieldset::make('UMK / Non UMK')
                        ->schema([
                            Forms\Components\Radio::make('umk_non_umk')
                                ->required()
                                ->options([
                                    'UMK' => 'UMK',
                                    'Non UMK' => 'Non UMK',
                                ])
                                ->inline()
                                ->default($item->umk_non_umk ?? ''),

                            Forms\Components\TextInput::make('nilai_umk')
                                ->label('Nilai UMK')
                                ->numeric()
                                ->prefix('Rp')
                                ->default($item->nilai_umk ?? 0),
                        ])
                        ->columns(1),
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
                        ->native(false)
                        ->default($item->serah_terima_pekerjaan ?? ''),

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
                        ->native(false)
                        ->default($item->penilaian_kinerja ?? ''),
                ])
                ->columns(2),
        ];
    }

    // âœ… FIELD KHUSUS UNTUK SWAKELOLA
    private function getSwakelolaFields($item): array
    {
        return [
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
                        ->numeric()
                        ->integer(),

                    Forms\Components\TextInput::make('pagu_rup')
                        ->label('Pagu RUP')
                        ->required()
                        ->numeric()
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
                        ->numeric()
                        ->prefix('Rp')
                        ->placeholder('0')
                        ->columnSpanFull(),
                ]),
        ];
    }

    // âœ… FIELD KHUSUS UNTUK SWAKELOLA
    private function getPengadaanDaruratFields($item): array
    {
        return [
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
                        ->numeric()
                        ->integer(),

                    Forms\Components\TextInput::make('pagu_rup')
                        ->label('Pagu RUP')
                        ->required()
                        ->numeric()
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
                        ->options([
                            'Barang' => 'Barang',
                            'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
                            'Jasa Konsultansi' => 'Jasa Konsultansi',
                            'Jasa Lainnya' => 'Jasa Lainnya',
                            'Terintegrasi' => 'Terintegrasi',
                        ])
                        ->required()
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

            Forms\Components\Section::make('Nilai Kontrak & Komponen')
                ->schema([
                    Forms\Components\TextInput::make('nilai_kontrak')
                        ->label('Nilai Kontrak')
                        ->numeric()
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
                                        ->options([
                                            'PDN' => 'PDN',
                                            'TKDN' => 'TKDN',
                                            'IMPOR' => 'IMPOR',
                                        ])
                                        ->required()
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
                                        ->label('Nilai PDN/TKDN/IMPOR')
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
                                                ->numeric()
                                                ->suffix('%')
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                                    $nilaiKontrak = $get('nilai_kontrak');
                                                    $persentase = $state ?: 0;
                                                    $hasil = $nilaiKontrak * ($persentase / 100);
                                                    $set('nilai_pdn_tkdn_impor', $hasil);
                                                }),

                                            Forms\Components\TextInput::make('nilai_pdn_tkdn_impor')
                                                ->label('Hasil TKDN')
                                                ->numeric()
                                                ->disabled()
                                                ->dehydrated()
                                                ->prefix('Rp'),
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
                                        ->options([
                                            'UMK' => 'UMK',
                                            'Non UMK' => 'Non UMK',
                                        ])
                                        ->required()
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
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->prefix('Rp')
                                ->columnSpan(1),
                        ])
                        ->columns(2),
                ]),
        ];
    }

    // âœ… FIELD KHUSUS UNTUK SWAKELOLA
    private function getnontenderFields($item): array
    {
        return [
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
                        ->numeric()
                        ->integer(),

                    Forms\Components\TextInput::make('pagu_rup')
                        ->label('Pagu RUP')
                        ->required()
                        ->numeric()
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
                        ->options([
                            'Barang' => 'Barang',
                            'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
                            'Jasa Konsultansi' => 'Jasa Konsultansi',
                            'Jasa Lainnya' => 'Jasa Lainnya',
                            'Terintegrasi' => 'Terintegrasi',
                        ])
                        ->required()
                        ->native(false),

                    Forms\Components\Section::make('Nilai Kontrak & Komponen')
                        ->schema([
                            Forms\Components\TextInput::make('nilai_kontrak')
                                ->label('Nilai Kontrak')
                                ->numeric()
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
                                                ->options([
                                                    'PDN' => 'PDN',
                                                    'TKDN' => 'TKDN',
                                                    'IMPOR' => 'IMPOR',
                                                ])
                                                ->required()
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
                                                ->label('Nilai PDN/TKDN/IMPOR')
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
                                                        ->numeric()
                                                        ->suffix('%')
                                                        ->minValue(0)
                                                        ->maxValue(100)
                                                        ->required()
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                                            $nilaiKontrak = $get('nilai_kontrak');
                                                            $persentase = $state ?: 0;
                                                            $hasil = $nilaiKontrak * ($persentase / 100);
                                                            $set('nilai_pdn_tkdn_impor', $hasil);
                                                        }),

                                                    Forms\Components\TextInput::make('nilai_pdn_tkdn_impor')
                                                        ->label('Hasil TKDN')
                                                        ->numeric()
                                                        ->disabled()
                                                        ->dehydrated()
                                                        ->prefix('Rp'),
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
                                                ->options([
                                                    'UMK' => 'UMK',
                                                    'Non UMK' => 'Non UMK',
                                                ])
                                                ->required()
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
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->prefix('Rp')
                                        ->columnSpan(1),
                                ])
                                ->columns(2),
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

                ]),
        ];
    }

    private function getViewFormSchema($record): array
    {
        $item = $record->item;

        if (!$item) {
            return [
                Forms\Components\Placeholder::make('error')
                    ->content('Data tidak ditemukan atau telah dihapus.'),
            ];
        }

        return [
            Forms\Components\Placeholder::make('jenis')
                ->label('Jenis Data')
                ->content($this->getTypeLabel($record->item_type)),

            Forms\Components\Placeholder::make('nama_pekerjaan')
                ->label('Nama Pekerjaan')
                ->content($item->nama_pekerjaan ?? '-'),

            Forms\Components\Placeholder::make('kode_rup')
                ->label('Kode RUP')
                ->content($item->kode_rup ?? '-'),

            Forms\Components\Placeholder::make('pagu_rup')
                ->label('Pagu RUP')
                ->content($item->pagu_rup ? 'Rp ' . number_format($item->pagu_rup, 0, ',', '.') : '-'),

            Forms\Components\Placeholder::make('nilai_kontrak')
                ->label('Nilai Kontrak')
                ->content($item->nilai_kontrak ? 'Rp ' . number_format($item->nilai_kontrak, 0, ',', '.') : '-'),
        ];
    }

    private function updateItemData($record, array $data)
    {
        $item = $record->item;

        if ($item) {
            // âœ… UPDATE SEMUA FIELD YANG ADA DI FORM PL
            $updateData = [
                // Field umum
                'nama_pekerjaan' => $data['nama_pekerjaan'],
                'kode_rup' => $data['kode_rup'],
                'pagu_rup' => $data['pagu_rup'],
                'nilai_kontrak' => $data['nilai_kontrak'],

                // Field khusus PL
                'tanggal_dibuat' => $data['tanggal_dibuat'] ?? $item->tanggal_dibuat,
                'kode_paket' => $data['kode_paket'] ?? $item->kode_paket,
                'jenis_pengadaan' => $data['jenis_pengadaan'] ?? $item->jenis_pengadaan,
                'pdn_tkdn_impor' => $data['pdn_tkdn_impor'] ?? $item->pdn_tkdn_impor,
                'nilai_pdn_tkdn_impor' => $data['nilai_pdn_tkdn_impor'] ?? $item->nilai_pdn_tkdn_impor,
                'persentase_tkdn' => $data['persentase_tkdn'] ?? $item->persentase_tkdn,
                'umk_non_umk' => $data['umk_non_umk'] ?? $item->umk_non_umk,
                'nilai_umk' => $data['nilai_umk'] ?? $item->nilai_umk,
                'serah_terima_pekerjaan' => $data['serah_terima_pekerjaan'] ?? $item->serah_terima_pekerjaan,
                'penilaian_kinerja' => $data['penilaian_kinerja'] ?? $item->penilaian_kinerja,
            ];

            $item->update($updateData);

            \Filament\Notifications\Notification::make()
                ->title('Data berhasil diperbarui!')
                ->success()
                ->send();
        }
    }

    private function getTypeLabel($type): string
    {
        return match ($type) {
            'App\Models\Pl' => 'Non Tender',
            'App\Models\Tender' => 'Tender',
            'App\Models\Epurcasing' => 'Epurcasing',
            'App\Models\Swakelola' => 'Pencatatan Swakelola',
            'App\Models\Nontender' => 'Pencatatan Non Tender',
            'App\Models\PengadaanDarurat' => 'Pencatatan Pengadaan Darurat',
            
            default => 'Unknown'
        };
    }

    private function getTypeColor($type): string
    {
        return match ($type) {
            'App\Models\Pl' => 'success',
            'App\Models\Tender' => 'danger',
            'App\Models\Epurcasing' => 'info',
            'App\Models\Swakelola' => 'primary',
            'App\Models\Nontender' => 'gray',
            'App\Models\PengadaanDarurat' => 'warning',
            default => 'gray'
        };
    }
    public function render(): View
{
    return view('livewire.rombongan-items-table');
}

    

    // public function render():View
    // {
    //     return view(static::$view);
    // }
}