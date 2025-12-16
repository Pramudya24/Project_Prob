<?php

namespace App\Filament\Opd\Resources\VerifikasiResource\Pages;

use App\Filament\Opd\Resources\VerifikasiResource;
use App\Models\Rombongan;
use App\Models\RombonganItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class EditDataProgres extends EditRecord
{
  protected static string $resource = VerifikasiResource::class;

  public function getTitle(): string
  {
    return 'Edit Data Progres - ' . $this->record->nama_rombongan;
  }

  public function getBreadcrumb(): string
  {
    return 'Edit Data';
  }

  public function mount($record): void
  {
    parent::mount($record);
    $this->form->fill($this->loadFormData());
  }

  public function form(Form $form): Form
  {
    $schemas = [];

    // 📋 CATATAN VERIFIKATOR
    $schemas[] = Forms\Components\Section::make('📋 Catatan dari Verifikator')
      ->description('Perhatikan catatan ini sebelum memperbaiki data')
      ->schema([
        Forms\Components\Placeholder::make('alert')
          ->label('')
          ->content(fn($record) => new HtmlString('
                        <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-300 dark:border-yellow-700 p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div class="flex-1">
                                    <h3 class="text-sm font-bold text-yellow-800 dark:text-yellow-200 mb-2">Catatan Umum:</h3>
                                    <p class="text-sm text-yellow-700 dark:text-yellow-300 whitespace-pre-wrap">' .
            ($record->keterangan_verifikasi ?? 'Tidak ada catatan umum') .
            '</p>
                                    <div class="mt-3 pt-3 border-t border-yellow-200 dark:border-yellow-800 flex items-center gap-4 text-xs text-yellow-600 dark:text-yellow-400">
                                        <span>📅 Dikembalikan: ' . $record->tanggal_verifikasi?->format('d/m/Y H:i') . '</span>
                                        <span>👤 Verifikator: ' . ($record->verifikator?->name ?? '-') . '</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ')),
      ])
      ->collapsible()
      ->collapsed(false);

    // 🔧 DYNAMIC FORMS PER ITEM
    $rombonganItems = $this->record->rombonganItems()->with(['item', 'fieldVerifications'])->get();

    foreach ($rombonganItems as $rombonganItem) {
      $item = $rombonganItem->item;
      if (!$item) continue;

      $itemType = $rombonganItem->item_type;
      $itemLabel = $this->getItemTypeLabel($itemType);

      // Get form schema based on item type
      $itemSchemas = $this->getFormSchemaForItem($rombonganItem, $item, $itemType);

      if (!empty($itemSchemas)) {
        $schemas[] = Forms\Components\Section::make('📦 ' . $itemLabel)
          ->description('Item: ' . ($item->nama_pekerjaan ?? 'Tidak ada nama'))
          ->schema($itemSchemas)
          ->collapsible()
          ->collapsed(false);
      }
    }

    $form = $form->schema($schemas);
    return $form->schema($schemas);
  }

  protected function loadFormData(): array
  {
    $data = ['items' => []];

    $rombonganItems = $this->record->rombonganItems()->with('item')->get();

    foreach ($rombonganItems as $rombonganItem) {
      if ($rombonganItem->item) {
        $data['items'][$rombonganItem->id] = $rombonganItem->item->toArray();
      }
    }

    \Log::info('Form Data:', $data); // Debug - cek di log

    return $data;
  }

  protected function getFormSchemaForItem($rombonganItem, $item, $itemType): array
  {
    $rombonganItemId = $rombonganItem->id;

    // Detect item type and call appropriate schema builder
    return match ($itemType) {
      'App\Models\Pl' => $this->getPlFormSchema($rombonganItemId, $item, $rombonganItem),
      'App\Models\Tender' => $this->getTenderFormSchema($rombonganItemId, $item, $rombonganItem),
      'App\Models\Epurcasing' => $this->getEpurcasingFormSchema($rombonganItemId, $item, $rombonganItem),
      'App\Models\Nontender' => $this->getNontenderFormSchema($rombonganItemId, $item, $rombonganItem),
      'App\Models\PengadaanDarurat' => $this->getPengadaanDaruratFormSchema($rombonganItemId, $item, $rombonganItem),
      'App\Models\Swakelola' => $this->getSwakelolaFormSchema($rombonganItemId, $item, $rombonganItem),
      default => [],
    };
  }

  // ==================== PL FORM SCHEMA ====================
  protected function getPlFormSchema($rombonganItemId, $item, $rombonganItem): array
  {
    return [
      // INFORMASI DASAR
      Forms\Components\Section::make('Informasi Dasar')
        ->schema([
          $this->makeDateField('tanggal_dibuat', $rombonganItemId, $rombonganItem, $item)
            ->label('Tanggal Dibuat')
            ->disabled()
            ->dehydrated(),

          $this->makeField('nama_pekerjaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Nama Pekerjaan')
            ->required()
            ->maxLength(255),

          $this->makeField('kode_rup', $rombonganItemId, $rombonganItem, $item)
            ->label('Kode RUP')
            ->required(),

          $this->makeField('pagu_rup', $rombonganItemId, $rombonganItem, $item)
            ->label('Pagu RUP')
            ->required()
            ->prefix('Rp'),

          $this->makeField('kode_paket', $rombonganItemId, $rombonganItem, $item)
            ->label('Kode Paket')
            ->required()
            ->maxLength(255),
        ])
        ->columns(2),

      // DETAIL PENGADAAN
      Forms\Components\Section::make('Detail Pengadaan')
        ->schema([
          $this->makeSelectField('jenis_pengadaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Jenis Pengadaan')
            ->required()
            ->live()
            ->options([
              'Barang' => 'Barang',
              'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
              'Jasa Konsultansi' => 'Jasa Konsultansi',
              'Jasa Lainnya' => 'Jasa Lainnya',
              'Terintegrasi' => 'Terintegrasi',
            ]),

          $this->makeSelectField('metode_pengadaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Metode Pengadaan')
            ->required()
            ->options([
              'EPengadaan Langsung' => 'E-Pengadaan Langsung',
              'EPenunjukan Langsung' => 'E-Penunjukan Langsung',
            ]),

          $this->makeFileField('summary_report', $rombonganItemId, $rombonganItem, $item)
            ->label('Summary Report')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg'])
            ->directory('summary-reports')
            ->downloadable()
            ->openable(),
        ])
        ->columns(2),

      // NILAI KONTRAK & KOMPONEN
      Forms\Components\Section::make('Nilai Kontrak & Komponen')
        ->schema([
          $this->makeField('nilai_kontrak', $rombonganItemId, $rombonganItem, $item)
            ->label('Nilai Kontrak')
            ->required()
            ->prefix('Rp')
            ->columnSpanFull(),

          // PDN/TKDN/IMPOR dengan Radio
          Forms\Components\Grid::make()
            ->schema([
              Forms\Components\Fieldset::make('PDN/TKDN/IMPOR')
                ->schema([
                  $this->makeRadioField('pdn_tkdn_impor', $rombonganItemId, $rombonganItem, $item)
                    ->label('Pilih salah satu')
                    ->required()
                    ->options([
                      'PDN' => 'PDN',
                      'TKDN' => 'TKDN',
                      'IMPOR' => 'IMPOR',
                    ])
                    ->inline()
                    ->columnSpanFull(),
                ])
                ->columnSpan(1),

              Forms\Components\Group::make()
                ->schema([
                  $this->makeField('nilai_pdn_tkdn_impor', $rombonganItemId, $rombonganItem, $item)
                    ->label('Nilai PDN/TKDN/IMPOR')
                    ->disabled()
                    ->dehydrated()
                    ->prefix('Rp'),
                ])
                ->columnSpan(1),
            ])
            ->columns(2),

          // UMK / Non UMK dengan Radio
          Forms\Components\Grid::make()
            ->schema([
              Forms\Components\Fieldset::make('UMK / Non UMK')
                ->schema([
                  $this->makeRadioField('umk_non_umk', $rombonganItemId, $rombonganItem, $item)
                    ->required()
                    ->options([
                      'UMK' => 'UMK',
                      'Non UMK' => 'Non UMK',
                    ])
                    ->inline()
                    ->columnSpanFull(),
                ])
                ->columnSpan(1),

              $this->makeField('nilai_umk', $rombonganItemId, $rombonganItem, $item)
                ->label('Nilai UMK')
                ->disabled()
                ->dehydrated()
                ->prefix('Rp')
                ->columnSpan(1),
            ])
            ->columns(2),
        ])
        ->columns(2),

      // STATUS PEKERJAAN
      Forms\Components\Section::make('Status Pekerjaan')
        ->schema([
          $this->makeSelectField('serah_terima_pekerjaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Serah Terima Pekerjaan')
            ->required()
            ->live()
            ->options([
              'BAST' => 'BAST',
              'On Progres' => 'On Progres',
            ]),

          $this->makeFileField('bast_document', $rombonganItemId, $rombonganItem, $item)
            ->label('Upload BAST')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
            ->directory('bast-documents')
            ->hidden(fn(Forms\Get $get) => $get("items.{$rombonganItemId}.serah_terima_pekerjaan") !== 'BAST')
            ->downloadable()
            ->openable(),

          $this->makeSelectField('penilaian_kinerja', $rombonganItemId, $rombonganItem, $item)
            ->label('Penilaian Kinerja')
            ->required()
            ->options([
              'Baik Sekali' => 'Baik Sekali',
              'Baik' => 'Baik',
              'Cukup' => 'Cukup',
              'Buruk' => 'Buruk',
              'Belum Dinilai' => 'Belum Dinilai',
            ]),
        ])
        ->columns(2),
    ];
  }

  // ==================== TENDER FORM SCHEMA ====================
  protected function getTenderFormSchema($rombonganItemId, $item, $rombonganItem): array
  {
    // Sama seperti PL
    return $this->getPlFormSchema($rombonganItemId, $item, $rombonganItem);
  }

  // ==================== EPURCHASING FORM SCHEMA ====================
  protected function getEpurcasingFormSchema($rombonganItemId, $item, $rombonganItem): array
  {
    return [
      Forms\Components\Section::make('Informasi Dasar')
        ->schema([
          $this->makeDateField('tanggal_dibuat', $rombonganItemId, $rombonganItem, $item)
            ->label('Tanggal Dibuat')
            ->disabled()
            ->dehydrated(),

          $this->makeField('nama_pekerjaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Nama Pekerjaan')
            ->required(),

          $this->makeField('kode_rup', $rombonganItemId, $rombonganItem, $item)
            ->label('Kode RUP')
            ->required(),

          $this->makeField('pagu_rup', $rombonganItemId, $rombonganItem, $item)
            ->label('Pagu RUP')
            ->required()
            ->prefix('Rp'),

          $this->makeField('kode_paket', $rombonganItemId, $rombonganItem, $item)
            ->label('Kode Paket')
            ->required(),
        ])
        ->columns(2),

      Forms\Components\Section::make('Detail Pengadaan')
        ->schema([
          $this->makeSelectField('jenis_pengadaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Jenis Pengadaan')
            ->live()
            ->required()
            ->options([
              'Barang' => 'Barang',
              'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
              'Jasa Konsultansi' => 'Jasa Konsultansi',
              'Jasa Lainnya' => 'Jasa Lainnya',
              'Terintegrasi' => 'Terintegrasi',
            ]),

          $this->makeSelectField('metode_pengadaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Metode Pengadaan')
            ->required()
            ->options([
              'E-Katalog' => 'E-Katalog',
              'Toko Daring' => 'Toko Daring',
            ]),

          $this->makeFileField('surat_pesanan', $rombonganItemId, $rombonganItem, $item)
            ->label('Surat Pesanan')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg'])
            ->directory('surat_pesanan')
            ->downloadable()
            ->openable(),
        ])
        ->columns(2),

      Forms\Components\Section::make('Nilai Kontrak & Komponen')
        ->schema([
          Forms\Components\TextInput::make('nilai_kontrak')
            ->label('Nilai Kontrak')
            ->rule('numeric')
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
                      // Hasil otomatis (READONLY)
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
          Forms\Components\Select::make('serah_terima')
            ->label('Serah Terima Pekerjaan')
            ->options([
              'BAST' => 'BAST',
              'On Progres' => 'On Progres',
            ])
            ->live()
            ->native(false),

          Forms\Components\FileUpload::make('bast_document')
            ->label('Upload BAST')
            ->required(fn(Forms\Get $get): bool => $get('serah_terima') === 'BAST')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
            ->maxSize(5120)
            ->directory('bast-documents')
            ->downloadable()
            ->openable()
            ->visible(
              fn(Forms\Get $get): bool =>
              $get('serah_terima') === 'BAST'
            ),

          Forms\Components\Select::make('penilaian_kinerja')
            ->label('Penilaian Kinerja')
            ->default('-')
            ->native(false)
            ->disabled(), // Menonaktifkan field
        ])
        ->columns(2),
    ];
  }

  // ==================== NONTENDER FORM SCHEMA ====================
  protected function getNontenderFormSchema($rombonganItemId, $item, $rombonganItem): array
  {
    return [
      Forms\Components\Section::make('Informasi Dasar')
        ->schema([
          $this->makeDateField('tanggal_dibuat', $rombonganItemId, $rombonganItem, $item)
            ->label('Tanggal Dibuat')
            ->disabled()
            ->dehydrated(),

          $this->makeField('nama_pekerjaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Nama Pekerjaan')
            ->required(),

          $this->makeField('kode_rup', $rombonganItemId, $rombonganItem, $item)
            ->label('Kode RUP')
            ->required(),

          $this->makeField('pagu_rup', $rombonganItemId, $rombonganItem, $item)
            ->label('Pagu RUP')
            ->required()
            ->prefix('Rp'),

          $this->makeField('kode_paket', $rombonganItemId, $rombonganItem, $item)
            ->label('Kode Paket')
            ->required(),
        ])
        ->columns(2),

      Forms\Components\Section::make('Detail Pengadaan')
        ->schema([
          $this->makeSelectField('jenis_pengadaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Jenis Pengadaan')
            ->live()
            ->required()
            ->options([
              'Barang' => 'Barang',
              'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
              'Jasa Konsultansi' => 'Jasa Konsultansi',
              'Jasa Lainnya' => 'Jasa Lainnya',
              'Terintegrasi' => 'Terintegrasi',
            ]),

          $this->makeSelectField('metode_pengadaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Metode Pengadaan')
            ->required()
            ->options([
              'Dikecualikan' => 'Dikecualikan',
              'Pengadaan Langsung' => 'Pengadaan Langsung',
              'Penunjukan Langsung' => 'Penunjukan Langsung',
            ]),
        ])
        ->columns(2),

      Forms\Components\Section::make('Nilai Kontrak & Komponen')
        ->schema([
          $this->makeField('nilai_kontrak', $rombonganItemId, $rombonganItem, $item)
            ->label('Nilai Kontrak')
            ->required()
            ->prefix('Rp')
            ->columnSpanFull(),

          Forms\Components\Grid::make()
            ->schema([
              Forms\Components\Fieldset::make('PDN/TKDN/IMPOR')
                ->schema([
                  $this->makeRadioField('pdn_tkdn_impor', $rombonganItemId, $rombonganItem, $item)
                    ->required()
                    ->options([
                      'PDN' => 'PDN',
                      'TKDN' => 'TKDN',
                      'IMPOR' => 'IMPOR',
                    ])
                    ->inline()
                    ->columnSpanFull(),
                ])
                ->columnSpan(1),

              Forms\Components\Group::make()
                ->schema([
                  $this->makeField('nilai_pdn_tkdn_impor', $rombonganItemId, $rombonganItem, $item)
                    ->label('Nilai PDN/TKDN/IMPOR')
                    ->disabled()
                    ->dehydrated()
                    ->prefix('Rp'),
                ])
                ->columnSpan(1),
            ])
            ->columns(2),

          Forms\Components\Grid::make()
            ->schema([
              Forms\Components\Fieldset::make('UMK / Non UMK')
                ->schema([
                  $this->makeRadioField('umk_non_umk', $rombonganItemId, $rombonganItem, $item)
                    ->required()
                    ->options([
                      'UMK' => 'UMK',
                      'Non UMK' => 'Non UMK',
                    ])
                    ->inline()
                    ->columnSpanFull(),
                ])
                ->columnSpan(1),

              $this->makeField('nilai_umk', $rombonganItemId, $rombonganItem, $item)
                ->label('Nilai UMK')
                ->disabled()
                ->dehydrated()
                ->prefix('Rp')
                ->columnSpan(1),
            ])
            ->columns(2),

          $this->makeFileField('realisasi', $rombonganItemId, $rombonganItem, $item)
            ->label('Realisasi')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg'])
            ->directory('realisasi')
            ->downloadable()
            ->openable(),
        ])
        ->columns(2),
    ];
  }

  // ==================== PENGADAAN DARURAT FORM SCHEMA ====================
  protected function getPengadaanDaruratFormSchema($rombonganItemId, $item, $rombonganItem): array
  {
    return [
      Forms\Components\Section::make('Informasi Dasar')
        ->schema([
          $this->makeDateField('tanggal_dibuat', $rombonganItemId, $rombonganItem, $item)
            ->label('Tanggal Dibuat')
            ->disabled()
            ->dehydrated(),

          $this->makeField('nama_pekerjaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Nama Pekerjaan')
            ->required(),

          $this->makeField('kode_rup', $rombonganItemId, $rombonganItem, $item)
            ->label('Kode RUP')
            ->required(),

          $this->makeField('pagu_rup', $rombonganItemId, $rombonganItem, $item)
            ->label('Pagu RUP')
            ->required()
            ->prefix('Rp'),

          $this->makeField('kode_paket', $rombonganItemId, $rombonganItem, $item)
            ->label('Kode Paket')
            ->required(),
        ])
        ->columns(2),

      Forms\Components\Section::make('Detail Pengadaan')
        ->schema([
          $this->makeSelectField('jenis_pengadaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Jenis Pengadaan')
            ->live()
            ->required()
            ->options([
              'Barang' => 'Barang',
              'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
              'Jasa Konsultansi' => 'Jasa Konsultansi',
              'Jasa Lainnya' => 'Jasa Lainnya',
              'Terintegrasi' => 'Terintegrasi',
            ]),

          $this->makeFileField('realisasi', $rombonganItemId, $rombonganItem, $item)
            ->label('Realisasi')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg'])
            ->directory('realisasi')
            ->downloadable()
            ->openable(),
        ])
        ->columns(2),

      Forms\Components\Section::make('Nilai Kontrak & Komponen')
        ->schema([
          $this->makeField('nilai_kontrak', $rombonganItemId, $rombonganItem, $item)
            ->label('Nilai Kontrak')
            ->required()
            ->prefix('Rp')
            ->columnSpanFull(),

          Forms\Components\Grid::make()
            ->schema([
              Forms\Components\Fieldset::make('PDN/TKDN/IMPOR')
                ->schema([
                  $this->makeRadioField('pdn_tkdn_impor', $rombonganItemId, $rombonganItem, $item)
                    ->required()
                    ->options([
                      'PDN' => 'PDN',
                      'TKDN' => 'TKDN',
                      'IMPOR' => 'IMPOR',
                    ])
                    
                    ->inline()
                    ->columnSpanFull(),
                ])
                ->columnSpan(1),

              Forms\Components\Group::make()
                ->schema([
                  $this->makeField('nilai_pdn_tkdn_impor', $rombonganItemId, $rombonganItem, $item)
                    ->label('Nilai PDN/TKDN/IMPOR')
                    ->disabled()
                    ->dehydrated()
                    ->prefix('Rp'),
                ])
                ->columnSpan(1),
            ])
            ->columns(2),

          Forms\Components\Grid::make()
            ->schema([
              Forms\Components\Fieldset::make('UMK / Non UMK')
                ->schema([
                  $this->makeRadioField('umk_non_umk', $rombonganItemId, $rombonganItem, $item)
                    ->required()
                    ->options([
                      'UMK' => 'UMK',
                      'Non UMK' => 'Non UMK',
                    ])
                    ->inline()
                    ->columnSpanFull(),
                ])
                ->columnSpan(1),

              $this->makeField('nilai_umk', $rombonganItemId, $rombonganItem, $item)
                ->label('Nilai UMK')
                ->disabled()
                ->dehydrated()
                ->prefix('Rp')
                ->columnSpan(1),
            ])
            ->columns(2),
        ]),
    ];
  }

  // ==================== SWAKELOLA FORM SCHEMA ====================
  protected function getSwakelolaFormSchema($rombonganItemId, $item, $rombonganItem): array
  {
    return [
      Forms\Components\Section::make('Informasi Dasar')
        ->schema([
          $this->makeDateField('tanggal_dibuat', $rombonganItemId, $rombonganItem, $item)
            ->label('Tanggal Dibuat')
            ->disabled()
            ->dehydrated(),

          $this->makeField('nama_pekerjaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Nama Pekerjaan')
            ->required(),

          $this->makeField('kode_rup', $rombonganItemId, $rombonganItem, $item)
            ->label('Kode RUP')
            ->required(),

          $this->makeField('pagu_rup', $rombonganItemId, $rombonganItem, $item)
            ->label('Pagu RUP')
            ->required()
            ->prefix('Rp'),

          $this->makeField('kode_paket', $rombonganItemId, $rombonganItem, $item)
            ->label('Kode Paket')
            ->required(),
        ])
        ->columns(2),

      Forms\Components\Section::make('Detail Pengadaan')
        ->schema([
          $this->makeSelectField('jenis_pengadaan', $rombonganItemId, $rombonganItem, $item)
            ->label('Jenis Pengadaan')
            ->live()
            ->required()
            ->options([
              'Barang' => 'Barang',
              'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
              'Jasa Konsultansi' => 'Jasa Konsultansi',
              'Jasa Lainnya' => 'Jasa Lainnya',
              'Terintegrasi' => 'Terintegrasi',
            ]),

          $this->makeFileField('realisasi', $rombonganItemId, $rombonganItem, $item)
            ->label('Realisasi')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg'])
            ->directory('realisasi')
            ->downloadable()
            ->openable(),
        ])
        ->columns(2),

      Forms\Components\Section::make('Nilai Kontrak')
        ->schema([
          $this->makeField('nilai_kontrak', $rombonganItemId, $rombonganItem, $item)
            ->label('Nilai Kontrak')
            ->required()
            ->prefix('Rp')
            ->columnSpanFull(),
        ]),
    ];
  }

  // ==================== HELPER METHODS ====================

  protected function makeField($fieldName, $rombonganItemId, $rombonganItem, $item): Forms\Components\TextInput
  {
    $verification = $rombonganItem->getFieldVerification($fieldName);
    $isVerified = $verification ? $verification->is_verified : false;
    $keterangan = $verification?->keterangan ?? '';
    $isPaten = in_array($fieldName, ['nama_opd', 'tanggal_dibuat']); // HANYA 2 INI YANG PATEN

    $field = Forms\Components\TextInput::make("items.{$rombonganItemId}.{$fieldName}")
      ->default($item->{$fieldName} ?? '');

    if ($isVerified || $isPaten) {
      $field->disabled()->dehydrated();
    }

    if (!empty($keterangan) && !$isVerified && !$isPaten) {
      $field->helperText(new HtmlString('
                <div class="mt-1 p-2 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 rounded">
                    <p class="text-xs font-semibold text-yellow-800 dark:text-yellow-200">💬 Catatan Verifikator:</p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">' . nl2br(htmlspecialchars($keterangan)) . '</p>
                </div>
            '));
    }

    return $field;
  }

  protected function makeDateField($fieldName, $rombonganItemId, $rombonganItem, $item): Forms\Components\DatePicker
  {
    $isPaten = in_array($fieldName, ['nama_opd', 'tanggal_dibuat']);

    $field = Forms\Components\DatePicker::make("items.{$rombonganItemId}.{$fieldName}")
      ->default($item->{$fieldName} ?? now())
      ->native(false)
      ->displayFormat('d/m/Y');

    if ($isPaten) {
      $field->disabled()->dehydrated();
    }

    return $field;
  }

  protected function makeSelectField($fieldName, $rombonganItemId, $rombonganItem, $item): Forms\Components\Select
  {
    $verification = $rombonganItem->getFieldVerification($fieldName);
    $isVerified = $verification ? $verification->is_verified : false;
    $keterangan = $verification?->keterangan ?? '';

    $field = Forms\Components\Select::make("items.{$rombonganItemId}.{$fieldName}")
      ->default($item->{$fieldName} ?? '')
      ->native(false);

    if ($isVerified) {
      $field->disabled()->dehydrated();
    }

    if (!empty($keterangan) && !$isVerified) {
      $field->helperText(new HtmlString('
                <div class="mt-1 p-2 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 rounded">
                    <p class="text-xs font-semibold text-yellow-800 dark:text-yellow-200">💬 Catatan Verifikator:</p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">' . nl2br(htmlspecialchars($keterangan)) . '</p>
                </div>
            '));
    }

    return $field;
  }

  protected function makeRadioField($fieldName, $rombonganItemId, $rombonganItem, $item): Forms\Components\Radio
  {
    $verification = $rombonganItem->getFieldVerification($fieldName);
    $isVerified = $verification ? $verification->is_verified : false;
    $keterangan = $verification?->keterangan ?? '';

    $field = Forms\Components\Radio::make("items.{$rombonganItemId}.{$fieldName}")
      ->default($item->{$fieldName} ?? '');

    if ($isVerified) {
      $field->disabled()->dehydrated();
    }

    if (!empty($keterangan) && !$isVerified) {
      $field->helperText(new HtmlString('
                <div class="mt-1 p-2 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 rounded">
                    <p class="text-xs font-semibold text-yellow-800 dark:text-yellow-200">💬 Catatan Verifikator:</p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">' . nl2br(htmlspecialchars($keterangan)) . '</p>
                </div>
            '));
    }

    return $field;
  }

  protected function makeFileField($fieldName, $rombonganItemId, $rombonganItem, $item): Forms\Components\FileUpload
  {
    $verification = $rombonganItem->getFieldVerification($fieldName);
    $isVerified = $verification ? $verification->is_verified : false;
    $keterangan = $verification?->keterangan ?? '';

    $field = Forms\Components\FileUpload::make("items.{$rombonganItemId}.{$fieldName}")
      ->default($item->{$fieldName} ?? '');

    if ($isVerified) {
      $field->disabled()->dehydrated();
    }

    if (!empty($keterangan) && !$isVerified) {
      $field->helperText(new HtmlString('
                <div class="mt-1 p-2 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 rounded">
                    <p class="text-xs font-semibold text-yellow-800 dark:text-yellow-200">💬 Catatan Verifikator:</p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">' . nl2br(htmlspecialchars($keterangan)) . '</p>
                </div>
            '));
    }

    return $field;
  }

  protected function getItemTypeLabel($itemType): string
  {
    return match ($itemType) {
      'App\Models\Pl' => 'PL (Penunjukan Langsung)',
      'App\Models\Tender' => 'Tender',
      'App\Models\Epurcasing' => 'E-Purchasing',
      'App\Models\Nontender' => 'Non Tender',
      'App\Models\PengadaanDarurat' => 'Pengadaan Darurat',
      'App\Models\Swakelola' => 'Swakelola',
      default => 'Unknown',
    };
  }

  protected function getHeaderActions(): array
  {
    return [
      Actions\Action::make('kirim_ulang')
        ->label('Kirim Ulang ke Verifikator')
        ->icon('heroicon-o-paper-airplane')
        ->color('success')
        ->requiresConfirmation()
        ->modalHeading('Kirim Ulang ke Verifikator?')
        ->modalDescription('Data yang sudah diperbaiki akan dikirim kembali ke verifikator.')
        ->modalSubmitActionLabel('Ya, Kirim Ulang')
        ->action(function () {
          $this->record->update([
            'status_pengiriman' => 'Terkirim ke Verifikator',
            'tanggal_masuk_verifikator' => now(),
            'status_verifikasi' => 'Belum',
          ]);

          Notification::make()
            ->title('✅ Berhasil Dikirim Ulang')
            ->body('Data "' . $this->record->nama_rombongan . '" telah dikirim ulang ke verifikator.')
            ->success()
            ->send();

          return redirect()->to(VerifikasiResource::getUrl('data-progres'));
        }),
    ];
  }

  protected function getFormActions(): array
  {
    return [
      Actions\Action::make('save')
        ->label('Simpan')
        ->color('primary')
        ->action('save'),

      Actions\Action::make('back')
        ->label('Batal')
        ->color('gray')
        ->url(fn() => VerifikasiResource::getUrl('data-progres')),
    ];
  }

  protected function mutateFormDataBeforeSave(array $data): array
  {
    if (isset($data['items']) && is_array($data['items'])) {
      foreach ($data['items'] as $rombonganItemId => $fields) {
        $rombonganItem = RombonganItem::find($rombonganItemId);
        if (!$rombonganItem) continue;

        $item = $rombonganItem->item;
        if (!$item) continue;

        foreach ($fields as $fieldName => $fieldValue) {
          if (in_array($fieldName, ['nama_opd', 'tanggal_dibuat', 'id'])) {
            continue;
          }

          $verification = $rombonganItem->getFieldVerification($fieldName);
          if ($verification && $verification->is_verified) {
            continue;
          }

          $item->{$fieldName} = $fieldValue;
        }

        $item->save();
      }
    }

    return [];
  }

  protected function afterSave(): void
  {
    Notification::make()
      ->title('✅ Data Berhasil Disimpan')
      ->body('Perubahan telah disimpan. Jangan lupa kirim ulang ke verifikator jika sudah selesai.')
      ->success()
      ->send();
    $this->redirect(VerifikasiResource::getUrl('data-progres'));
  }
}
