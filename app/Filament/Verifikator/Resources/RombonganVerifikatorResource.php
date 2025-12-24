<?php

namespace App\Filament\Verifikator\Resources;

use App\Filament\Verifikator\Resources\RombonganVerifikatorResource\Pages;
use App\Models\Rombongan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class RombonganVerifikatorResource extends Resource
{
    protected static ?string $model = Rombongan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Verifikasi Rombongan';
    protected static ?string $modelLabel = 'Verifikasi Rombongan';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $schemas = [];

        // SECTION: Data Rombongan
        $schemas[] = Forms\Components\Section::make('Data Rombongan')
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
            ])
            ->columns(3);

        // SECTION: Detail Item - Loop per Type (Accordion)
        $record = $form->getRecord();

        if ($record) {
            $grouped = $record->getGroupedItemsWithFields();

            foreach ($grouped as $type => $data) {
                // ✅ SECTION ACCORDION PER TYPE (PL, Tender, dll)
                $schemas[] = Forms\Components\Section::make('📦 ' . strtoupper($data['label']))
                    ->description(count($data['items']) . ' item')
                    ->schema([
                        Forms\Components\Placeholder::make("items_table_{$type}")
                            ->label('')
                            ->content(function () use ($data) {
                                $html = '<div class="space-y-6">';

                                foreach ($data['items'] as $item) {
                                    $progress = $item['progress'];

                                    // HEADER ITEM
                                    $html .= '<div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900">';
                                    $html .= '<div class="flex justify-between items-center mb-4">';
                                    $html .= '<h4 class="text-lg font-semibold text-gray-900 dark:text-white">Item: ' . htmlspecialchars($item['nama_pekerjaan']) . '</h4>';

                                    // Button Centang Semua
                                    if ($progress['percentage'] < 100) {
                                        $html .= '<button type="button" ';
                                        $html .= 'onclick="event.preventDefault(); event.stopPropagation(); verifyAllFields(' . $item['rombongan_item_id'] . ')" ';
                                        $html .= 'class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition">';
                                        $html .= '✓ Centang Semua';
                                        $html .= '</button>';
                                    }

                                    $html .= '</div>';

                                    // TABEL FIELD
                                    $html .= '<div class="overflow-x-auto">';
                                    $html .= '<table class="w-full border-collapse border border-gray-300 dark:border-gray-600 text-sm">';

                                    // Table Header
                                    $html .= '<thead><tr class="bg-gray-100 dark:bg-gray-700">';
                                    $html .= '<th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold w-16">No</th>';
                                    $html .= '<th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold">Uraian</th>';
                                    $html .= '<th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold">Keterangan</th>';
                                    $html .= '<th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center font-semibold w-48">Verifikasi</th>';
                                    $html .= '<th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold w-64">Catatan</th>';
                                    $html .= '</tr></thead>';

                                    // Table Body
                                    $html .= '<tbody>';
                                    $no = 1;

                                    foreach ($item['fields'] as $field) {
                                        $isVerified = $field['is_verified'];
                                        $fieldValue = $field['field_value'];
                                        $fieldName = $field['field_name'];
                                        $fieldLabel = $field['field_label'];
                                        $keterangan = $field['keterangan'] ?? '';
                                        $isPatenField = in_array($fieldName, ['nama_opd', 'tanggal_dibuat']);

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

                                        // Handle file preview
                                        $isImageField = false;
                                        $isPdfField = false;
                                        $fileUrl = null;
                                        $fileExists = false;

                                        if ($fieldValue && $fieldValue !== '-') {
                                            $cleanFilename = trim($fieldValue);
                                            $isPdfField = str_ends_with(strtolower($cleanFilename), '.pdf');

                                            if (!$isPdfField) {
                                                $imageKeywords = ['foto', 'gambar', 'image', 'photo', 'picture', 'realisasi'];
                                                foreach ($imageKeywords as $keyword) {
                                                    if (str_contains(strtolower($fieldName), $keyword)) {
                                                        $isImageField = true;
                                                        break;
                                                    }
                                                }

                                                if (!$isImageField) {
                                                    $imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'];
                                                    foreach ($imageExtensions as $ext) {
                                                        if (str_ends_with(strtolower($cleanFilename), $ext)) {
                                                            $isImageField = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }

                                            if ($isPdfField || $isImageField) {
                                                try {
                                                    $fileExists = Storage::disk('public')->exists($cleanFilename);
                                                    if ($fileExists) {
                                                        // ✅ PERUBAHAN DI SINI:
                                                        $fileUrl = Storage::disk('public')->url($cleanFilename);
                                                    }
                                                } catch (\Exception $e) {
                                                    \Log::error('Error generating file URL:', [
                                                        'field_value' => $fieldValue,
                                                        'error' => $e->getMessage()
                                                    ]);
                                                }
                                            }
                                        }

                                        // Row class
                                        $rowClass = (!$isPatenField && $isVerified) ? 'bg-green-50 dark:bg-green-900/20' : '';

                                        $html .= '<tr class="' . $rowClass . '">';

                                        // Kolom No
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">' . $no . '</td>';

                                        // Kolom Uraian
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 font-medium">';
                                        $html .= htmlspecialchars($fieldLabel);
                                        $html .= '</td>';

                                        // Kolom Keterangan (File Preview)
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3">';

                                        if ($isPdfField && $fileExists && $fileUrl) {
                                            $html .= '<div class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/30 transition cursor-pointer" onclick="event.preventDefault(); event.stopPropagation(); previewPdf(\'' . addslashes($fileUrl) . '\', \'' . addslashes(htmlspecialchars(basename($cleanFilename))) . '\')">';
                                            $html .= '<svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>';
                                            $html .= '<div><div class="font-medium text-gray-900 dark:text-gray-100">' . htmlspecialchars(basename($cleanFilename)) . '</div>';
                                            $html .= '<div class="text-sm text-green-600 dark:text-green-400">✓ Klik untuk preview</div></div>';
                                            $html .= '</div>';
                                        } elseif ($isImageField && $fileExists && $fileUrl) {
                                            $html .= '<div class="flex items-center gap-3">';
                                            $html .= '<img src="' . $fileUrl . '" alt="Preview" class="w-32 h-32 object-cover rounded border cursor-pointer hover:opacity-80 transition" onclick="event.preventDefault(); event.stopPropagation(); openImageModal(\'' . addslashes($fileUrl) . '\')">';
                                            $html .= '<div class="text-xs text-gray-500"><a href="' . $fileUrl . '" target="_blank" class="text-primary-600 hover:underline">Lihat full</a>';
                                            $html .= '<div class="mt-1 text-green-600">✓ ' . htmlspecialchars(basename($cleanFilename)) . '</div></div>';
                                            $html .= '</div>';
                                        } else {
                                            $html .= htmlspecialchars($fieldValue ?? '-');
                                        }

                                        $html .= '</td>';

                                        // Kolom Verifikasi
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">';

                                        if ($isPatenField) {
                                            $html .= '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">🔒 Paten</span>';
                                        } else {
                                            $checkboxId = 'verify_' . $item['rombongan_item_id'] . '_' . $fieldName;
                                            $checked = $isVerified ? 'checked' : '';

                                            $html .= '<div class="flex items-center justify-center gap-3">';
                                            $html .= '<label class="flex items-center cursor-pointer">';
                                            $html .= '<input type="checkbox" id="' . $checkboxId . '" class="field-checkbox rounded border-gray-300 text-primary-600" ';
                                            $html .= 'data-rombongan-item-id="' . $item['rombongan_item_id'] . '" data-field-name="' . $fieldName . '" ' . $checked . ' ';
                                            $html .= 'onchange="handleVerificationChange(' . $item['rombongan_item_id'] . ', \'' . $fieldName . '\', this.checked)">';
                                            $html .= '<span class="ml-2 text-sm">Verifikasi</span></label>';

                                            if ($isVerified) {
                                                $html .= '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">✓ Sudah</span>';
                                            } else {
                                                $html .= '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">○ Belum</span>';
                                            }

                                            $html .= '</div>';
                                        }

                                        $html .= '</td>';

                                        // Kolom Catatan
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3">';

                                        if (!$isPatenField) {
                                            $html .= '<textarea id="catatan_' . $item['rombongan_item_id'] . '_' . $fieldName . '" ';
                                            $html .= 'class="w-full rounded border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600" rows="2" ';
                                            $html .= 'placeholder="Catatan..." onblur="saveCatatan(' . $item['rombongan_item_id'] . ', \'' . $fieldName . '\', this.value)">';
                                            $html .= htmlspecialchars($keterangan);
                                            $html .= '</textarea>';
                                        }

                                        $html .= '</td>';
                                        $html .= '</tr>';
                                        $no++;
                                    }

                                    $html .= '</tbody></table></div></div>';
                                }

                                $html .= '</div>';

                                // Modals + JavaScript (sama seperti sebelumnya)
                                $html .= self::getModalsAndJavaScript();

                                return new HtmlString($html);
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(false); // Buka semua accordion secara default
            }
        }

        return $form->schema($schemas);
    }

    // Method terpisah untuk Modals + JavaScript
    protected static function getModalsAndJavaScript(): string
    {
        return <<<'HTML'
<!-- Modal Gambar -->
<div id="imageModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center p-4" onclick="closeImageModal()">
    <div class="w-full max-w-3xl max-h-[85vh] bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-2xl flex flex-col" onclick="event.stopPropagation()">
        <div class="px-6 py-4 bg-gray-100 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Preview Gambar</h3>
            <div class="flex gap-2">
                <button type="button" onclick="downloadImage()" class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download
                </button>
                <button type="button" onclick="closeImageModal()" class="px-4 py-2 text-sm font-medium bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    ✕ Tutup
                </button>
            </div>
        </div>
        <div class="flex-1 overflow-auto p-8 flex items-center justify-center bg-gray-50 dark:bg-gray-900">
            <img id="modalImage" src="" class="max-w-full max-h-[80vh] w-auto h-auto rounded-lg shadow-2xl border-2 border-gray-200 dark:border-gray-700">
        </div>
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-400 text-center">
            <span id="imageInfo">Klik di luar gambar atau tekan ESC untuk menutup</span>
        </div>
    </div>
</div>

<!-- Modal PDF -->
<div id="pdfModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-95 flex items-center justify-center p-4" onclick="closePdfModal()">
    <div class="relative w-full max-w-6xl h-[90vh] bg-white dark:bg-gray-800 rounded-lg shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center px-6 py-4 bg-gray-100 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" id="pdfModalTitle">Preview PDF</h3>
            <div class="flex gap-2">
                <a id="pdfDownloadBtn" href="#" download class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download PDF
                </a>
                <button type="button" onclick="closePdfModal()" class="px-4 py-2 text-sm font-medium bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    ✕ Tutup
                </button>
            </div>
        </div>
        <div class="relative h-[calc(95vh-5rem)]">
            <div id="pdfLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800 z-10">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mx-auto mb-4"></div>
                    <p class="text-gray-600 dark:text-gray-400 font-medium">Loading PDF...</p>
                </div>
            </div>
            <iframe id="pdfIframe" 
                    class="w-full h-full border-0" 
                    title="PDF Preview"
                    sandbox="allow-scripts allow-same-origin" 
                    onload="document.getElementById('pdfLoading').classList.add('hidden')"
                    onerror="document.getElementById('pdfLoading').innerHTML='<div class=\'text-center p-8\'><p class=\'text-red-600 font-medium\'>❌ Gagal memuat PDF</p></div>';">
            </iframe>
        </div>
    </div>
</div>

<script>
function handleVerificationChange(rombonganItemId, fieldName, isChecked) {
    fetch("/verifikator/rombongan-verifikators/verify-field", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector("meta[name='csrf-token']").content
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
            const checkbox = document.getElementById('verify_' + rombonganItemId + '_' + fieldName);
            if (checkbox) {
                const row = checkbox.closest("tr");
                if (row) {
                    if (isChecked) {
                        row.classList.add("bg-green-50", "dark:bg-green-900/20");
                    } else {
                        row.classList.remove("bg-green-50", "dark:bg-green-900/20");
                    }
                }
                
                const badge = checkbox.closest("td").querySelector(".inline-flex:last-child");
                if (badge) {
                    if (isChecked) {
                        badge.className = "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100";
                        badge.textContent = "✓ Sudah";
                    } else {
                        badge.className = "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100";
                        badge.textContent = "○ Belum";
                    }
                }
            }
        }
    })
    .catch(error => console.error("Error:", error));
}

function saveCatatan(rombonganItemId, fieldName, catatan) {
    fetch("/verifikator/rombongan-verifikators/save-catatan", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": document.querySelector("meta[name='csrf-token']").content
        },
        body: JSON.stringify({
            rombongan_item_id: rombonganItemId,
            field_name: fieldName,
            catatan: catatan
        })
    })
    .then(response => response.json())
    .then(data => data.success && console.log("Catatan tersimpan"))
    .catch(error => console.error("Error:", error));
}

function verifyAllFields(rombonganItemId) {
    if (!confirm("Centang semua field untuk item ini?")) return;
    
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = "Memproses...";
    
    fetch("/verifikator/rombongan-verifikators/verify-all-fields", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector("meta[name='csrf-token']").content
        },
        body: JSON.stringify({ rombongan_item_id: rombonganItemId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('input[data-rombongan-item-id="' + rombonganItemId + '"]').forEach(checkbox => {
                checkbox.checked = true;
                const row = checkbox.closest("tr");
                if (row) row.classList.add("bg-green-50", "dark:bg-green-900/20");
                const badge = checkbox.closest("td").querySelector(".inline-flex:last-child");
                if (badge) {
                    badge.className = "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100";
                    badge.textContent = "✓ Sudah";
                }
            });
            btn.style.display = "none";
            alert("✓ Semua field berhasil diverifikasi!");
        }
    })
    .catch(error => {
        console.error("Error:", error);
        btn.disabled = false;
        btn.textContent = "✓ Centang Semua";
    });
}

// ✅ FUNGSI GAMBAR - DENGAN DOWNLOAD
let currentImageUrl = '';

function openImageModal(imageUrl) {
    currentImageUrl = imageUrl;
    const modalImage = document.getElementById("modalImage");
    const imageInfo = document.getElementById("imageInfo");
    
    modalImage.src = imageUrl;
    
    const filename = imageUrl.split('/').pop();
    imageInfo.textContent = 'File: ' + decodeURIComponent(filename);
    
    document.getElementById("imageModal").classList.remove("hidden");
    document.body.style.overflow = "hidden";
}

function downloadImage() {
    if (!currentImageUrl) return;
    
    const filename = currentImageUrl.split('/').pop();
    const link = document.createElement('a');
    link.href = currentImageUrl;
    link.download = decodeURIComponent(filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function closeImageModal() {
    document.getElementById("imageModal").classList.add("hidden");
    document.body.style.overflow = "auto";
    currentImageUrl = '';
}

// ✅ FUNGSI PDF - DENGAN DOWNLOAD
function previewPdf(pdfUrl, filename) {
    const modal = document.getElementById("pdfModal");
    const iframe = document.getElementById("pdfIframe");
    const loading = document.getElementById("pdfLoading");
    const downloadBtn = document.getElementById("pdfDownloadBtn");
    
    // Reset state
    loading.classList.remove("hidden");
    iframe.src = "";
    
    // Set data
    document.getElementById("pdfModalTitle").textContent = "Preview: " + filename;
    downloadBtn.href = pdfUrl;
    downloadBtn.download = filename;
    
    // Show modal
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
    
    // Load PDF
    setTimeout(() => {
        iframe.src = pdfUrl;
    }, 100);
}

function closePdfModal() {
    const modal = document.getElementById("pdfModal");
    const iframe = document.getElementById("pdfIframe");
    
    modal.classList.add("hidden");
    iframe.src = "";
    document.body.style.overflow = "auto";
}

// ✅ ESC KEY HANDLER
document.addEventListener("keydown", e => {
    if (e.key === "Escape") {
        closeImageModal();
        closePdfModal();
    }
});
</script>
HTML;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) =>
                $query->where('status_pengiriman', 'Terkirim ke Verifikator')
            )
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

                Tables\Columns\TextColumn::make('status_pengiriman')
                    ->label('Status Pengiriman')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Belum Dikirim' => 'gray',
                        'Terkirim ke Verifikator' => 'info',
                        'Dikirim ke Data Progres' => 'warning',
                        'Dikirim ke Data Sudah Progres' => 'success',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Verifikasi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info'),

                Tables\Actions\Action::make('kirim_ke_data_progres')
                    ->label('Kirim ke Data Progres')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(function ($record) {
                        $progress = $record->getVerificationProgress();
                        return (int) $progress['percentage'] < 100
                            && $record->status_pengiriman === 'Terkirim ke Verifikator';
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Kirim ke Data Progres OPD')
                    ->modalDescription(fn($record) => 'Rombongan "' . $record->nama_rombongan . '" akan dikembalikan ke OPD untuk diperbaiki.')
                    ->modalSubmitActionLabel('Ya, Kirim')
                    ->action(function ($record) {
                        $record->update([
                            'status_pengiriman' => 'Data Progres',
                            'status_verifikasi' => 'Belum',
                            'lolos_verif' => false,
                            'keterangan_verifikasi' => 'Perlu revisi - Verifikasi belum lengkap',
                            'tanggal_verifikasi' => now(),
                            'verifikator_id' => auth()->id(),
                        ]);
                        Notification::make()
                            ->title('✅ Berhasil Dikirim ke Data Progres')
                            ->body('Rombongan "' . $record->nama_rombongan . '" telah dikirim ke Data Progres OPD.')
                            ->success()
                            ->duration(5000)
                            ->send();
                    }),

                Tables\Actions\Action::make('kirim_ke_data_sudah_progres')
                    ->label('Kirim ke Data Sudah Progres')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function ($record) {
                        $progress = $record->getVerificationProgress();
                        return (int) $progress['percentage'] === 100
                            && $record->status_pengiriman === 'Terkirim ke Verifikator';
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Kirim ke Pembuatan SPM')
                    ->modalDescription(fn($record) => 'Rombongan "' . $record->nama_rombongan . '" Silahkan Buat No. SPM')
                    ->modalSubmitActionLabel('Ya, Kirim')
                    ->action(function ($record) {
                        $record->update([
                            'status_pengiriman' => 'Data Sudah Progres',
                            'status_verifikasi' => 'Sudah',
                            'lolos_verif' => true,
                            'keterangan_verifikasi' => 'Sudah diverifikasi 100%',
                            'tanggal_verifikasi' => now(),
                            'verifikator_id' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Berhasil Dikirim ke Data Sudah Progres')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Tidak Ada Data')
            ->emptyStateDescription('Tidak ada data yang perlu di verifikasi.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
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

    public static function getEloquentQuery(): Builder
    {
        return Rombongan::query()
            ->withoutGlobalScope('opd_filter')
            ->where('status_pengiriman', 'Terkirim ke Verifikator')
            ->orderBy('tanggal_masuk_verifikator', 'desc');
    }
}