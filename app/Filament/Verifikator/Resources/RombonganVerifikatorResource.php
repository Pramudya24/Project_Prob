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
                                    $progressColor = match (true) {
                                        $progress['percentage'] == 100 => 'bg-green-500',
                                        $progress['percentage'] >= 50 => 'bg-yellow-500',
                                        default => 'bg-red-500'
                                    };

                                    // Sub-header nama item dengan button centang semua
                                    $html .= '<div class="mb-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">';
                                    $html .= '<div class="flex justify-between items-center mb-2">';
                                    $html .= '<h4 class="text-lg font-semibold">Item: ' . htmlspecialchars($item['nama_pekerjaan']) . '</h4>';
                                    $html .= '<div class="flex items-center gap-3">';
                                    $html .= '<span class="text-sm font-medium">';
                                    $html .= 'Progress: ' . $progress['verified'] . '/' . $progress['total'] . ' field (' . $progress['percentage'] . '%)';
                                    $html .= '</span>';
                                    
                                    // Button Centang Semua
                                    if ($progress['percentage'] < 100) {
                                        $html .= '<button ';
                                        $html .= 'onclick="verifyAllFields(' . $item['rombongan_item_id'] . ')" ';
                                        $html .= 'class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition">';
                                        $html .= '✓ Centang Semua';
                                        $html .= '</button>';
                                    }
                                    
                                    $html .= '</div>';
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
                                    $html .= '<th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold w-64">Catatan</th>';
                                    $html .= '</tr>';
                                    $html .= '</thead>';

                                    // Table body
                                    $html .= '<tbody>';

                                    $no = 1;
                                    foreach ($item['fields'] as $field) {
                                        $isVerified = $field['is_verified'];
                                        $fieldValue = $field['field_value'];
                                        $fieldName = $field['field_name'];
                                        $keterangan = $field['keterangan'] ?? '';

                                        // Format nilai
                                        if (in_array($fieldName, ['pagu_rup', 'nilai_kontrak', 'total_nilai']) && is_numeric($fieldValue)) {
                                            $fieldValue = 'Rp ' . number_format($fieldValue, 0, ',', '.');
                                        }

                                        if (str_contains($fieldName, 'tanggal') && $fieldValue && $fieldValue !== '-') {
                                            try {
                                                $fieldValue = \Carbon\Carbon::parse($fieldValue)->format('d/m/Y');
                                            } catch (\Exception $e) {
                                                // Keep original
                                            }
                                        }

                                        // Cek apakah field gambar
                                        $isImageField = false;
                                        $imageUrl = null;
                                        
                                        if ($fieldValue && $fieldValue !== '-') {
                                            $imageKeywords = ['foto', 'gambar', 'image', 'photo', 'picture'];
                                            foreach ($imageKeywords as $keyword) {
                                                if (str_contains(strtolower($fieldName), $keyword)) {
                                                    $isImageField = true;
                                                    break;
                                                }
                                            }
                                            
                                            if (!$isImageField) {
                                                $imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'];
                                                foreach ($imageExtensions as $ext) {
                                                    if (str_ends_with(strtolower($fieldValue), $ext)) {
                                                        $isImageField = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            if ($isImageField) {
                                                $imageUrl = '/private-file/' . $fieldValue;
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

                                        // Kolom Keterangan (dengan preview gambar jika ada)
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3">';
                                        
                                        if ($isImageField && $imageUrl) {
                                            $html .= '<div class="flex items-center gap-3">';
                                            $html .= '<img src="' . $imageUrl . '" ';
                                            $html .= 'alt="Preview" ';
                                            $html .= 'class="w-32 h-32 object-cover rounded border cursor-pointer hover:opacity-80 transition" ';
                                            $html .= 'onclick="openImageModal(\'' . $imageUrl . '\')" ';
                                            $html .= 'onerror="this.src=\'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ENo Image%3C/text%3E%3C/svg%3E\'"';
                                            $html .= '>';
                                            $html .= '<div class="text-xs text-gray-500">';
                                            $html .= '<a href="' . $imageUrl . '" target="_blank" class="text-primary-600 hover:underline">Lihat full</a>';
                                            $html .= '<div class="mt-1">' . htmlspecialchars($fieldValue) . '</div>';
                                            $html .= '</div>';
                                            $html .= '</div>';
                                        } else {
                                            $html .= htmlspecialchars($fieldValue ?? '-');
                                        }
                                        
                                        $html .= '</td>';

                                        // Kolom Verifikasi
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">';

                                        $checkboxId = 'verify_' . $item['rombongan_item_id'] . '_' . $fieldName;
                                        $checked = $isVerified ? 'checked' : '';

                                        $html .= '<div class="flex items-center justify-center gap-3">';

                                        // Checkbox
                                        $html .= '<label class="flex items-center cursor-pointer">';
                                        $html .= '<input type="checkbox" ';
                                        $html .= 'id="' . $checkboxId . '" ';
                                        $html .= 'class="field-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500" ';
                                        $html .= 'data-rombongan-item-id="' . $item['rombongan_item_id'] . '" ';
                                        $html .= 'data-field-name="' . $fieldName . '" ';
                                        $html .= $checked . ' ';
                                        $html .= 'onchange="handleVerificationChange(' . $item['rombongan_item_id'] . ', \'' . $fieldName . '\', this.checked)"';
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

                                        // Kolom Catatan
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3">';
                                        $html .= '<textarea ';
                                        $html .= 'id="catatan_' . $item['rombongan_item_id'] . '_' . $fieldName . '" ';
                                        $html .= 'class="w-full rounded border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600" ';
                                        $html .= 'rows="2" ';
                                        $html .= 'placeholder="Catatan untuk field ini..." ';
                                        $html .= 'onblur="saveCatatan(' . $item['rombongan_item_id'] . ', \'' . $fieldName . '\', this.value)"';
                                        $html .= '>' . htmlspecialchars($keterangan) . '</textarea>';
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

                            // Modal untuk lightbox gambar
                            $html .= '
                            <div id="imageModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-75 flex items-center justify-center p-4" onclick="closeImageModal()">
                                <div class="relative max-w-4xl max-h-full">
                                    <button onclick="closeImageModal()" class="absolute -top-10 right-0 text-white text-3xl font-bold hover:text-gray-300">&times;</button>
                                    <img id="modalImage" src="" class="max-w-full max-h-screen object-contain rounded" onclick="event.stopPropagation()">
                                </div>
                            </div>';

                            // JavaScript
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
                                            window.location.reload();
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Error:", error);
                                        alert("Gagal menyimpan verifikasi");
                                    });
                                }
                                
                                function saveCatatan(rombonganItemId, fieldName, catatan) {
                                    fetch("/verifikator/rombongan-verifikators/save-catatan", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": document.querySelector("meta[name=\'csrf-token\']").content
                                        },
                                        body: JSON.stringify({
                                            rombongan_item_id: rombonganItemId,
                                            field_name: fieldName,
                                            catatan: catatan
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            console.log("Catatan tersimpan");
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Error:", error);
                                    });
                                }
                                
                                function verifyAllFields(rombonganItemId) {
                                    if (!confirm("Centang semua field untuk item ini?")) {
                                        return;
                                    }
                                    
                                    fetch("/verifikator/rombongan-verifikators/verify-all-fields", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": document.querySelector("meta[name=\'csrf-token\']").content
                                        },
                                        body: JSON.stringify({
                                            rombongan_item_id: rombonganItemId
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            window.location.reload();
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Error:", error);
                                        alert("Gagal centang semua field");
                                    });
                                }
                                
                                function openImageModal(imageUrl) {
                                    document.getElementById("modalImage").src = imageUrl;
                                    document.getElementById("imageModal").classList.remove("hidden");
                                    document.body.style.overflow = "hidden";
                                }
                                
                                function closeImageModal() {
                                    document.getElementById("imageModal").classList.add("hidden");
                                    document.body.style.overflow = "auto";
                                }
                                
                                document.addEventListener("keydown", function(e) {
                                    if (e.key === "Escape") {
                                        closeImageModal();
                                    }
                                });
                            </script>';

                            return new HtmlString($html);
                        }),
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
                    ->color(
                        fn($record) =>
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