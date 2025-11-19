<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use App\Filament\Opd\Resources\Rombongan\RombonganResource;
use App\Models\Rombongan;
use App\Models\RombonganItem;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EditRombongan extends EditRecord
{
    protected static string $resource = RombonganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view')
                ->label('Lihat Detail Rombongan')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => RombonganResource::getUrl('view', ['record' => $this->record])),
                
            Actions\DeleteAction::make()
                ->label('Hapus Rombongan'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Rombongan')
                    ->description('Informasi dasar rombongan')
                    ->schema([
                        Forms\Components\TextInput::make('nama_rombongan')
                            ->label('Nama Rombongan')
                            ->disabled(),
                            
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull()
                            ->disabled(),
                    ])
                    ->columns(1),
                    
                Forms\Components\Section::make('Statistik')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('total_items')
                                    ->label('Total Data')
                                    ->content(fn ($record) => $record->items()->count()),
                                    
                                Forms\Components\Placeholder::make('total_nilai')
                                    ->label('Total Nilai')
                                    ->content(fn ($record) => 'Rp ' . number_format($record->items()->with('item')->get()->sum(function($item) {
                                        return $item->item->nilai_kontrak ?? 0;
                                    }), 0, ',', '.')),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Data dalam Rombongan')
                    ->schema([
                        $this->getItemsTable(),
                    ])
                    ->collapsible(false),
            ]);
    }

    protected function getItemsTable(): Forms\Components\Component
    {
        return Forms\Components\Livewire::make(RombonganItemsTable::class, [
            'rombonganId' => $this->record->id,
        ]);
    }

    private function getEditFormSchema($record): array
    {
        $item = $record->item;
        
        if (!$item) {
            return [
                Forms\Components\Placeholder::make('error')
                    ->content('Data tidak ditemukan atau telah dihapus.'),
            ];
        }
        
        // ✅ FORM UMUM UNTUK SEMUA JENIS DATA
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

        // ✅ FIELD TAMBAHAN BERDASARKAN JENIS DATA
        $additionalFields = match($record->item_type) {
            'App\Models\Pl' => $this->getPlFields($item),
            'App\Models\Tender' => $this->getTenderFields($item),
            'App\Models\Epurcasing' => $this->getEpurcasingFields($item),
            'App\Models\Swakelola' => $this->getSwakelolaFields($item),
            'App\Models\PengadaanDarurat' => $this->getPengadaanDaruratFields($item),
            'App\Models\NonTender' => $this->getNonTenderFields($item),
            default => []
        };

        return array_merge($commonFields, $additionalFields);
    }

    // ✅ FIELD KHUSUS UNTUK PL
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

    // ✅ FIELD KHUSUS UNTUK TENDER - DIPERBAIKI
    private function getTenderFields($item): array
    {
        return [
            Forms\Components\Section::make('Informasi Dasar Tender')
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

    // ✅ FIELD KHUSUS UNTUK E-PURCHASING
    private function getEpurcasingFields($item): array
    {
        return [
            Forms\Components\Section::make('Informasi Dasar E-Purchasing')
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

    // ✅ FIELD KHUSUS UNTUK SWAKELOLA - DIPERBAIKI
    private function getSwakelolaFields($item): array
    {
        return [
            Forms\Components\Section::make('Informasi Dasar Swakelola')
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
        ];
    }

    // ✅ FIELD KHUSUS UNTUK PENGADAAN DARURAT - BARU
    private function getPengadaanDaruratFields($item): array
    {
        return [
            Forms\Components\Section::make('Informasi Dasar Pengadaan Darurat')
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
        ];
    }

    // ✅ FIELD KHUSUS UNTUK NON TENDER - BARU
    private function getNonTenderFields($item): array
    {
        return [
            Forms\Components\Section::make('Informasi Dasar Non Tender')
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

    // ✅ UPDATE METHOD YANG BENAR UNTUK SEMUA JENIS DATA
    private function updateItemData($record, array $data)
    {
        $item = $record->item;
        
        if ($item) {
            // ✅ UPDATE BERDASARKAN JENIS DATA
            switch($record->item_type) {
                case 'App\Models\Pl':
                    $this->updatePlData($item, $data);
                    break;
                case 'App\Models\Tender':
                    $this->updateTenderData($item, $data);
                    break;
                case 'App\Models\Epurcasing':
                    $this->updateEpurcasingData($item, $data);
                    break;
                case 'App\Models\Swakelola':
                    $this->updateSwakelolaData($item, $data);
                    break;
                case 'App\Models\PengadaanDarurat':
                    $this->updatePengadaanDaruratData($item, $data);
                    break;
                case 'App\Models\NonTender':
                    $this->updateNonTenderData($item, $data);
                    break;
                default:
                    // Update dasar untuk jenis data lainnya
                    $item->update([
                        'nama_pekerjaan' => $data['nama_pekerjaan'],
                        'kode_rup' => $data['kode_rup'],
                        'pagu_rup' => $data['pagu_rup'],
                        'nilai_kontrak' => $data['nilai_kontrak'],
                    ]);
            }
            
            \Filament\Notifications\Notification::make()
                ->title('Data berhasil diperbarui!')
                ->success()
                ->send();
        }
    }

    // ✅ METHOD UPDATE SPESIFIK UNTUK SETIAP JENIS DATA
    private function updatePlData($item, array $data)
    {
        $item->update([
            'nama_pekerjaan' => $data['nama_pekerjaan'],
            'kode_rup' => $data['kode_rup'],
            'pagu_rup' => $data['pagu_rup'],
            'nilai_kontrak' => $data['nilai_kontrak'],
            'tanggal_dibuat' => $data['tanggal_dibuat'],
            'kode_paket' => $data['kode_paket'],
            'jenis_pengadaan' => $data['jenis_pengadaan'],
            'pdn_tkdn_impor' => $data['pdn_tkdn_impor'],
            'nilai_pdn_tkdn_impor' => $data['nilai_pdn_tkdn_impor'] ?? 0,
            'persentase_tkdn' => $data['persentase_tkdn'] ?? 0,
            'umk_non_umk' => $data['umk_non_umk'],
            'nilai_umk' => $data['nilai_umk'] ?? 0,
            'serah_terima_pekerjaan' => $data['serah_terima_pekerjaan'],
            'penilaian_kinerja' => $data['penilaian_kinerja'],
        ]);
    }

    private function updateTenderData($item, array $data)
    {
        $item->update([
            'nama_pekerjaan' => $data['nama_pekerjaan'],
            'kode_rup' => $data['kode_rup'],
            'pagu_rup' => $data['pagu_rup'],
            'nilai_kontrak' => $data['nilai_kontrak'],
            'tanggal_dibuat' => $data['tanggal_dibuat'],
            'kode_paket' => $data['kode_paket'],
            'jenis_pengadaan' => $data['jenis_pengadaan'],
            'pdn_tkdn_impor' => $data['pdn_tkdn_impor'],
            'nilai_pdn_tkdn_impor' => $data['nilai_pdn_tkdn_impor'] ?? 0,
            'persentase_tkdn' => $data['persentase_tkdn'] ?? 0,
            'umk_non_umk' => $data['umk_non_umk'],
            'nilai_umk' => $data['nilai_umk'] ?? 0,
            'serah_terima_pekerjaan' => $data['serah_terima_pekerjaan'],
            'penilaian_kinerja' => $data['penilaian_kinerja'],
        ]);
    }

    private function updateEpurcasingData($item, array $data)
    {
        $item->update([
            'nama_pekerjaan' => $data['nama_pekerjaan'],
            'kode_rup' => $data['kode_rup'],
            'pagu_rup' => $data['pagu_rup'],
            'nilai_kontrak' => $data['nilai_kontrak'],
            'tanggal_dibuat' => $data['tanggal_dibuat'],
            'kode_paket' => $data['kode_paket'],
            'jenis_pengadaan' => $data['jenis_pengadaan'],
            'pdn_tkdn_impor' => $data['pdn_tkdn_impor'],
            'nilai_pdn_tkdn_impor' => $data['nilai_pdn_tkdn_impor'] ?? 0,
            'persentase_tkdn' => $data['persentase_tkdn'] ?? 0,
            'umk_non_umk' => $data['umk_non_umk'],
            'nilai_umk' => $data['nilai_umk'] ?? 0,
            'serah_terima_pekerjaan' => $data['serah_terima_pekerjaan'],
            'penilaian_kinerja' => $data['penilaian_kinerja'],
        ]);
    }

    private function updateSwakelolaData($item, array $data)
    {
        $item->update([
            'nama_pekerjaan' => $data['nama_pekerjaan'],
            'kode_rup' => $data['kode_rup'],
            'pagu_rup' => $data['pagu_rup'],
            'nilai_kontrak' => $data['nilai_kontrak'],
            'tanggal_dibuat' => $data['tanggal_dibuat'],
            'kode_paket' => $data['kode_paket'],
            'jenis_pengadaan' => $data['jenis_pengadaan'],
        ]);
    }

    private function updatePengadaanDaruratData($item, array $data)
    {
        $item->update([
            'nama_pekerjaan' => $data['nama_pekerjaan'],
            'kode_rup' => $data['kode_rup'],
            'pagu_rup' => $data['pagu_rup'],
            'nilai_kontrak' => $data['nilai_kontrak'],
            'tanggal_dibuat' => $data['tanggal_dibuat'],
            'kode_paket' => $data['kode_paket'],
            'jenis_pengadaan' => $data['jenis_pengadaan'],
        ]);
    }

    private function updateNonTenderData($item, array $data)
    {
        $item->update([
            'nama_pekerjaan' => $data['nama_pekerjaan'],
            'kode_rup' => $data['kode_rup'],
            'pagu_rup' => $data['pagu_rup'],
            'nilai_kontrak' => $data['nilai_kontrak'],
            'tanggal_dibuat' => $data['tanggal_dibuat'],
            'kode_paket' => $data['kode_paket'],
            'jenis_pengadaan' => $data['jenis_pengadaan'],
        ]);
    }

    private function getTypeLabel($type): string
    {
        return match($type) {
            'App\Models\Pl' => 'PL',
            'App\Models\Tender' => 'Tender',
            'App\Models\Epurcasing' => 'E-Purchasing',
            'App\Models\Swakelola' => 'Swakelola',
            'App\Models\PengadaanDarurat' => 'Pengadaan Darurat',
            'App\Models\NonTender' => 'Non Tender',
            default => 'Unknown'
        };
    }

    private function getTypeColor($type): string
    {
        return match($type) {
            'App\Models\Pl' => 'success',
            'App\Models\Tender' => 'primary',
            'App\Models\Epurcasing' => 'info',
            'App\Models\Swakelola' => 'warning',
            'App\Models\NonTender' => 'warning',
            'App\Models\PengadaanDarurat' => 'warning',
            default => 'gray'
        };
    }

    private function getItemUrl($record): ?string
    {
        $item = $record->item;
        if (!$item) return null;

        return match($record->item_type) {
            'App\Models\Pl' => \App\Filament\Opd\Resources\Pls\PlResource::getUrl('view', ['record' => $item->id]),
            'App\Models\Tender' => \App\Filament\Opd\Resources\TenderResource::getUrl('view', ['record' => $item->id]),
            'App\Models\Epurcasing' => \App\Filament\Opd\Resources\EpurcasingResource::getUrl('view', ['record' => $item->id]),
            'App\Models\Swakelola' => \App\Filament\Opd\Resources\SwakelolaResource::getUrl('view', ['record' => $item->id]),
            'App\Models\PengadaanDarurat' => \App\Filament\Opd\Resources\PengadaanDaruratResource::getUrl('view', ['record' => $item->id]),
            'App\Models\NonTender' => \App\Filament\Opd\Resources\NonTenderResource::getUrl('view', ['record' => $item->id]),
            default => null
        };
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record->id]);
    }
}