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

    // Load data untuk form
    $formData = [];

    $rombonganItems = $this->record->rombonganItems()->with('item')->get();

    foreach ($rombonganItems as $rombonganItem) {
      if ($rombonganItem->item) {
        $formData['items'][$rombonganItem->id] = $rombonganItem->item->toArray();
      }
    }

    // Debug - cek data yang akan di-fill
    \Log::info('EditDataProgres - Form Data:', [
      'rombongan_id' => $this->record->id,
      'total_items' => count($formData['items'] ?? []),
      'form_data' => $formData
    ]);

    // Fill form
    $this->form->fill($formData);
  }

  public function form(Form $form): Form
  {
    $schemas = [];
    // Debug 1: Cek record
    \Log::info('=== DEBUG EDIT DATA PROGRES ===');
    \Log::info('Record ID: ' . $this->record->id);
    \Log::info('Record Nama: ' . $this->record->nama_rombongan);

    // CATATAN VERIFIKATOR
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
            htmlspecialchars($record->keterangan_verifikasi ?? 'Tidak ada catatan umum') .
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

    // Debug 2: Cek rombongan items
    $rombonganItems = $this->record->rombonganItems()->with(['item', 'fieldVerifications'])->get();
    \Log::info('Total Rombongan Items: ' . $rombonganItems->count());

    foreach ($rombonganItems as $index => $rombonganItem) {
      \Log::info("Item #{$index}:", [
        'rombongan_item_id' => $rombonganItem->id,
        'item_type' => $rombonganItem->item_type,
        'has_item' => $rombonganItem->item ? 'YES' : 'NO',
        'item_id' => $rombonganItem->item_id ?? 'NULL',
      ]);

      if ($rombonganItem->item) {
        \Log::info("  Item Data:", $rombonganItem->item->toArray());
      }
    }

    // DYNAMIC FORMS PER ITEM
    if ($rombonganItems->count() === 0) {
      $schemas[] = Forms\Components\Section::make('⚠️ Tidak Ada Item')
        ->description('Tidak ada item dalam rombongan ini')
        ->schema([
          Forms\Components\Placeholder::make('no_items')
            ->label('')
            ->content(new HtmlString('<p class="text-red-600">Rombongan ini tidak memiliki item. Hubungi administrator.</p>')),
        ]);
    }

    foreach ($rombonganItems as $rombonganItem) {
      $item = $rombonganItem->item;

      if (!$item) {
        \Log::warning('Item NULL untuk rombongan_item_id: ' . $rombonganItem->id);

        $schemas[] = Forms\Components\Section::make('⚠️ Item Error')
          ->description('Item ID: ' . ($rombonganItem->item_id ?? 'NULL'))
          ->schema([
            Forms\Components\Placeholder::make('error_' . $rombonganItem->id)
              ->label('')
              ->content(new HtmlString('<p class="text-red-600">Item tidak ditemukan untuk rombongan_item_id: ' . $rombonganItem->id . '</p>')),
          ]);
        continue;
      }

      $itemType = $rombonganItem->item_type;
      $itemLabel = $this->getItemTypeLabel($itemType);

      \Log::info("Building schema for item:", [
        'item_type' => $itemType,
        'item_label' => $itemLabel,
      ]);

      // Get form schema based on item type
      $itemSchemas = $this->getFormSchemaForItem($rombonganItem, $item, $itemType);

      if (empty($itemSchemas)) {
        \Log::warning('Empty schema for item type: ' . $itemType);
        continue;
      }

      $schemas[] = Forms\Components\Section::make('📦 ' . $itemLabel)
        ->description('Item: ' . ($item->nama_pekerjaan ?? 'Tidak ada nama'))
        ->schema($itemSchemas)
        ->collapsible()
        ->collapsed(false);
    }

    \Log::info('Total Schemas Created: ' . count($schemas));
    \Log::info('=== END DEBUG ===');

    return $form->schema($schemas);
  }

  protected function getFormSchemaForItem($rombonganItem, $item, $itemType): array
  {
    $rombonganItemId = $rombonganItem->id;

    // ✅ NORMALIZE item type (case insensitive + handle backslash)
    $itemType = strtolower(str_replace('\\', '\\', $itemType));

    \Log::info('Normalized item type:', ['original' => $rombonganItem->item_type, 'normalized' => $itemType]);

    // Detect item type and call appropriate schema builder
    return match ($itemType) {
      'app\models\pl' => $this->getPlFormSchema($rombonganItemId, $item, $rombonganItem),
      'app\models\tender' => $this->getTenderFormSchema($rombonganItemId, $item, $rombonganItem),
      'app\models\epurcasing' => $this->getEpurcasingFormSchema($rombonganItemId, $item, $rombonganItem),
      'app\models\nontender' => $this->getNontenderFormSchema($rombonganItemId, $item, $rombonganItem),
      'app\models\pengadaandarurat' => $this->getPengadaanDaruratFormSchema($rombonganItemId, $item, $rombonganItem),
      'app\models\swakelola' => $this->getSwakelolaFormSchema($rombonganItemId, $item, $rombonganItem),
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
            ->disk('private')
            ->directory('summary-reports')
            ->visibility('private')
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
            ->disk('private')
            ->directory('bast-document')
            ->visibility('private')
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

          $this->makeFileField('summary_report', $rombonganItemId, $rombonganItem, $item)
            ->label('Summary Report')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg'])
            ->disk('private')
            ->directory('summary-reports')
            ->visibility('private')
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

          $this->makeFileField('BAST', $rombonganItemId, $rombonganItem, $item)
            ->label('Upload BAST')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
            ->disk('private')
            ->directory('BAST')
            ->visibility('private')
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
            ->disk('private')
            ->directory('surat_pesanan')
            ->visibility('private')
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

      Forms\Components\Section::make('Status Pekerjaan')
        ->schema([
          $this->makeSelectField('serah_terima', $rombonganItemId, $rombonganItem, $item)
            ->label('Serah Terima Pekerjaan')
            ->required()
            ->live()
            ->options([
              'BAST' => 'BAST',
              'On Progres' => 'On Progres',
            ]),

          $this->makeFileField('BAST', $rombonganItemId, $rombonganItem, $item)
            ->label('Upload BAST')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
            ->disk('private')
            ->directory('BAST')
            ->visibility('private')
            ->hidden(fn(Forms\Get $get) => $get("items.{$rombonganItemId}.serah_terima") !== 'BAST')
            ->downloadable()
            ->openable(),

          $this->makeSelectField('penilaian_kinerja', $rombonganItemId, $rombonganItem, $item)
            ->label('Penilaian Kinerja')
            ->disabled()
            ->default('-'),
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
            ->disk('private')
            ->directory('realisasi')
            ->visibility('private')
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
            ->disk('private')
            ->directory('realisasi')
            ->visibility('private')
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
            ->disk('private')
            ->directory('realisasi')
            ->visibility('private')
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
      ->disk('private')
      ->default($item->{$fieldName} ?? null)
      ->downloadable()
      ->openable()
      ->previewable(true);

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
    // ✅ NORMALIZE item type
    $itemType = strtolower($itemType);

    return match ($itemType) {
      'app\models\pl' => 'PL (Penunjukan Langsung)',
      'app\models\tender' => 'Tender',
      'app\models\epurcasing' => 'E-Purchasing',
      'app\models\nontender' => 'Non Tender',
      'app\models\pengadaandarurat' => 'Pengadaan Darurat',
      'app\models\swakelola' => 'Swakelola',
      default => 'Unknown Type: ' . $itemType,
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
