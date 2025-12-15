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
                                    // $html .= 'Progress: ' . $progress['verified'] . '/' . $progress['total'] . ' field (' . $progress['percentage'] . '%)';
                                    $html .= '</span>';

                                    // Button Centang Semua
                                    if ($progress['percentage'] < 100) {
                                        $html .= '<button ';
                                        $html .= 'onclick="verifyAllFields(' . $item['rombongan_item_id'] . ')" ';
                                        $html .= 'class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition">';
                                        $html .= 'Centang Semua';
                                        $html .= '</button>';
                                    }

                                    $html .= '</div>';
                                    $html .= '</div>';

                                    // Progress bar
                                    // $html .= '<div class="w-full bg-gray-200 rounded-full h-2">';
                                    // $html .= '<div class="' . $progressColor . ' h-2 rounded-full transition-all" style="width: ' . $progress['percentage'] . '%"></div>';
                                    // $html .= '</div>';
                                    // $html .= '</div>';

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
                                        $fieldLabel = $field['field_label'];
                                        $keterangan = $field['keterangan'] ?? '';

                                        // ✅ Tentukan apakah field paten (tidak ada checkbox)
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

                                        // Cek apakah field gambar atau PDF
                                        $isImageField = false;
                                        $isPdfField = false;
                                        $fileUrl = null;

                                        if ($fieldValue && $fieldValue !== '-') {
                                            // ✅ FIX: Generate URL yang benar untuk semua jenis file

                                            // Cek apakah ini file (PDF atau gambar)
                                            $isPdfField = str_ends_with(strtolower($fieldValue), '.pdf');

                                            // Cek apakah ini gambar
                                            $isImageField = false;
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
                                                        if (str_ends_with(strtolower($fieldValue), $ext)) {
                                                            $isImageField = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }

                                            // CARI YANG INI:
                                            if ($isPdfField || $isImageField) {
                                                $cleanFilename = trim($fieldValue);

                                                try {
                                                    // 🔥 CEK FILE ADA DULU SEBELUM GENERATE URL
                                                    if (Storage::disk('private')->exists($cleanFilename)) {
                                                        $fileUrl = route('private.file', ['path' => $cleanFilename]);
                                                    } else {
                                                        // File ga ada, tetap generate URL tapi log warning
                                                        $fileUrl = route('private.file', ['path' => $cleanFilename]);

                                                        \Log::warning('File missing for field', [
                                                            'field_name' => $fieldName,
                                                            'expected_path' => $cleanFilename,
                                                            'full_path' => Storage::disk('private')->path($cleanFilename),
                                                            'available_files' => Storage::disk('private')->files('summary-reports'),
                                                        ]);
                                                    }

                                                    \Log::debug('Generated file URL:', [
                                                        'field_name' => $fieldName,
                                                        'field_value' => $fieldValue,
                                                        'clean_filename' => $cleanFilename,
                                                        'file_url' => $fileUrl,
                                                        'exists' => Storage::disk('private')->exists($cleanFilename),
                                                    ]);
                                                } catch (\Exception $e) {
                                                    \Log::error('Error generating file URL:', [
                                                        'field_value' => $fieldValue,
                                                        'error' => $e->getMessage()
                                                    ]);
                                                    $fileUrl = '#';
                                                }
                                            }
                                        }

                                        // ✅ Tentukan row class - hanya untuk field non-paten yang terverifikasi
                                        $rowClass = '';
                                        if (!$isPatenField && $isVerified) {
                                            $rowClass = 'bg-green-50 dark:bg-green-900/20';
                                        }

                                        $html .= '<tr class="' . $rowClass . '">';

                                        // Kolom No
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">' . $no . '</td>';

                                        // Kolom Uraian
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 font-medium">';
                                        $html .= htmlspecialchars($fieldLabel);
                                        $html .= '</td>';

                                        // Kolom Keterangan (dengan preview gambar/PDF)
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3">';

                                        if ($isPdfField && $fileUrl) {
                                            // ✅ PREVIEW PDF
                                            $html .= '<div class="space-y-3">';

                                            // Card PDF dengan icon
                                            $html .= '<div class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/30 transition cursor-pointer" onclick="previewPdf(\'' . $fileUrl . '\', \'' . htmlspecialchars(basename($fieldValue)) . '\')">';
                                            $html .= '<div class="flex-shrink-0 p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">';
                                            $html .= '<svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">';
                                            $html .= '<path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>';
                                            $html .= '<path d="M8 10a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>';
                                            $html .= '<path d="M8 13a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>';
                                            $html .= '</svg>';
                                            $html .= '</div>';
                                            $html .= '<div class="flex-1 min-w-0">';
                                            $html .= '<div class="font-medium text-gray-900 dark:text-gray-100 truncate">' . htmlspecialchars(basename($fieldValue)) . '</div>';
                                            $html .= '<div class="text-sm text-gray-500 dark:text-gray-400">PDF Document • Klik untuk preview</div>';
                                            $html .= '</div>';
                                            $html .= '</div>';

                                            // Action buttons - ✅ GANTI URL dengan yang sudah fix
                                            $html .= '<div class="flex flex-wrap gap-2">';
                                            $html .= '<button onclick="event.stopPropagation(); previewPdf(\'' . $fileUrl . '\', \'' . htmlspecialchars(basename($fieldValue)) . '\')" class="px-3 py-1.5 text-sm font-medium bg-blue-600 text-white rounded hover:bg-blue-700 transition inline-flex items-center gap-1">';
                                            $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
                                            $html .= 'Preview';
                                            $html .= '</button>';
                                            $html .= '<a href="' . $fileUrl . '" target="_blank" class="px-3 py-1.5 text-sm font-medium bg-gray-600 text-white rounded hover:bg-gray-700 transition inline-flex items-center gap-1">';
                                            $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>';
                                            $html .= 'Buka Tab Baru';
                                            $html .= '</a>';
                                            $html .= '</div>';

                                            $html .= '</div>';
                                        } elseif ($isImageField && $fileUrl) {
                                            // PREVIEW GAMBAR
                                            $html .= '<div class="flex items-center gap-3">';

                                            // ✅ GANTI: onclick openImageModal dengan URL yang sudah fix
                                            $html .= '<img src="' . $fileUrl . '" ';
                                            $html .= 'alt="Preview" ';
                                            $html .= 'class="w-32 h-32 object-cover rounded border cursor-pointer hover:opacity-80 transition" ';
                                            $html .= 'onclick="openImageModal(\'' . $fileUrl . '\')" ';
                                            $html .= 'onerror="console.error(\'Gagal load gambar: ' . $fileUrl . '\', this.src=\'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ENo Image%3C/text%3E%3C/svg%3E\')"';
                                            $html .= '>';

                                            $html .= '<div class="text-xs text-gray-500 dark:text-gray-400">';
                                            $html .= '<a href="' . $fileUrl . '" target="_blank" class="text-primary-600 hover:underline">Lihat full</a>';
                                            $html .= '<div class="mt-1">' . htmlspecialchars($fieldValue) . '</div>';
                                            $html .= '</div>';
                                            $html .= '</div>';
                                        } else {
                                            // Text biasa
                                            $html .= htmlspecialchars($fieldValue ?? '-');
                                        }

                                        $html .= '</td>';

                                        // ✅ KOLOM VERIFIKASI - PERUBAHAN UTAMA DI SINI
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">';

                                        if ($isPatenField) {
                                            // ✅ FIELD PATEN: Tampilkan badge "Paten" tanpa checkbox
                                            $html .= '<div class="flex items-center justify-center gap-2">';
                                            $html .= '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100 border border-gray-300 dark:border-gray-700">';
                                            $html .= '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>';
                                            $html .= 'Paten';
                                            $html .= '</span>';
                                            $html .= '<span class="text-xs text-gray-500">(tidak diverifikasi)</span>';
                                            $html .= '</div>';
                                        } else {
                                            // ✅ FIELD BIASA: Tampilkan checkbox verifikasi
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
                                        }

                                        $html .= '</td>';

                                        // ✅ KOLOM CATATAN - Field paten tetap bisa ada catatan (opsional)
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3">';

                                        if ($isPatenField) {
                                            // ✅ Untuk field paten: textarea readonly atau placeholder info
                                            $html .= '<div class="text-center">';
                                            $html .= '<span class="text-sm text-gray-500 italic">Field paten - tidak memerlukan catatan</span>';
                                            $html .= '</div>';
                                        } else {
                                            // ✅ Untuk field biasa: textarea biasa
                                            $html .= '<textarea ';
                                            $html .= 'id="catatan_' . $item['rombongan_item_id'] . '_' . $fieldName . '" ';
                                            $html .= 'class="w-full rounded border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600" ';
                                            $html .= 'rows="2" ';
                                            $html .= 'placeholder="Catatan untuk field ini..." ';
                                            $html .= 'onblur="saveCatatan(' . $item['rombongan_item_id'] . ', \'' . $fieldName . '\', this.value)"';
                                            $html .= '>' . htmlspecialchars($keterangan) . '</textarea>';
                                        }

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

                            // Modal untuk lightbox gambar - ✅ UKURAN MEDIUM
                            $html .= '
                            <div id="imageModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-75 flex items-center justify-center p-4" onclick="closeImageModal()">
                                <div class="max-w-2xl max-h-[80vh]">
                                    <button onclick="closeImageModal()" class="absolute -top-10 right-0 text-white text-3xl font-bold hover:text-gray-300 transition z-10">&times;</button>
                                    
                                    <!-- ✅ CONTAINER UKURAN MEDIUM -->
                                    <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-2xl max-w-3xl max-h-[85vh] w-full flex flex-col">
                                        <!-- Header -->
                                        <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600 flex justify-between items-center">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Preview Gambar</h3>
                                            <div class="flex gap-2">
                                                <button onclick="window.downloadImage()" class="px-3 py-1.5 text-sm font-medium bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                                    ⬇️ Download
                                                </button>
                                                <button onclick="closeImageModal()" class="px-3 py-1.5 text-sm font-medium bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                                                    ✕ Tutup
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- ✅ IMAGE CONTAINER UKURAN MEDIUM -->
                                        <div class="flex-1 overflow-auto p-6 flex items-center justify-center">
                                            <img id="modalImage" 
                                                src="" 
                                                class="max-w-full max-h-[60vh] w-auto h-auto object-contain rounded-lg shadow-lg border border-gray-200 dark:border-gray-700" 
                                                onclick="event.stopPropagation()"
                                                onerror="this.onerror=null; this.src=\'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmaWxsPSIjOTk5IiBmb250LXNpemU9IjE2Ij5JbWFnZSBub3QgZm91bmQ8L3RleHQ+PC9zdmc+\'">
                                        </div>
                                        
                                        <!-- Footer info -->
                                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-300 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 text-center">
                                            <span id="imageInfo">Klik di luar gambar untuk menutup • Scroll untuk zoom</span>
                                        </div>
                                    </div>
                                </div>
                            </div>';

                            // Modal untuk preview PDF - ✅ FIX TIDAK REDIRECT
                            $html .= '
                        <div id="pdfModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center p-4">
                            <div class="relative w-full max-w-6xl h-[90vh] bg-white dark:bg-gray-800 rounded-lg shadow-2xl overflow-hidden">
                                <div class="flex justify-between items-center p-4 bg-gray-100 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" id="pdfModalTitle">Preview PDF</h3>
                                    <div class="flex gap-2">
                                        <a id="pdfDownloadBtn" href="#" download class="px-3 py-1.5 text-sm font-medium bg-blue-600 text-white rounded hover:bg-blue-700 transition inline-flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Download
                                        </a>
                                        <button onclick="closePdfModal()" class="px-3 py-1.5 text-sm font-medium bg-gray-600 text-white rounded hover:bg-gray-700 transition">Tutup</button>
                                    </div>
                                </div>
                                <!-- ✅ TAMBAH CONTAINER DENGAN LOADING -->
                                <div class="h-full relative">
                                    <div id="pdfLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800 z-10">
                                        <div class="text-center">
                                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-3"></div>
                                            <p class="text-gray-600 dark:text-gray-400">Loading PDF...</p>
                                        </div>
                                    </div>
                                    <!-- ✅ IFRAME DENGAN SANDBOX UNTUK ISOLASI -->
                                    <iframe id="pdfIframe" 
                                            class="w-full h-full border-0" 
                                            title="PDF Preview"
                                            sandbox="allow-scripts allow-same-origin" 
                                            onload="document.getElementById(\'pdfLoading\').classList.add(\'hidden\')"
                                            onerror="document.getElementById(\'pdfLoading\').innerHTML=\'<div class=\\\'text-center p-8\\\'><p class=\\\'text-red-600\\\'>Gagal load PDF</p></div>\';">
                                    </iframe>
                                </div>
                            </div>
                        </div>';

                            $html .= <<<JS
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
                    
                    const badge = checkbox.closest("td").querySelector(".inline-flex");
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
                updateProgressBar(rombonganItemId);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Gagal menyimpan verifikasi. Silakan coba lagi.");
        });
    }
    
    function updateProgressBar(rombonganItemId) {
        const checkboxes = document.querySelectorAll('input[data-rombongan-item-id="' + rombonganItemId + '"]');
        const total = checkboxes.length;
        const verified = Array.from(checkboxes).filter(cb => cb.checked).length;
        const percentage = Math.round((verified / total) * 100);
        
        checkboxes[0]?.closest(".border").querySelectorAll(".mb-4").forEach(div => {
            const progressText = div.querySelector("span");
            if (progressText && progressText.textContent.includes("Progress:")) {
                progressText.textContent = 'Progress: ' + verified + '/' + total + ' field (' + percentage + '%)';
            }
            
            const progressBar = div.querySelector(".h-2");
            if (progressBar) {
                progressBar.style.width = percentage + '%';
                if (percentage === 100) {
                    progressBar.className = "bg-green-500 h-2 rounded-full transition-all";
                } else if (percentage >= 50) {
                    progressBar.className = "bg-yellow-500 h-2 rounded-full transition-all";
                } else {
                    progressBar.className = "bg-red-500 h-2 rounded-full transition-all";
                }
            }
        });
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
        
        const btn = event.target;
        btn.disabled = true;
        btn.textContent = "Memproses...";
        
        fetch("/verifikator/rombongan-verifikators/verify-all-fields", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector("meta[name='csrf-token']").content
            },
            body: JSON.stringify({
                rombongan_item_id: rombonganItemId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const checkboxes = document.querySelectorAll('input[data-rombongan-item-id="' + rombonganItemId + '"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                    const row = checkbox.closest("tr");
                    if (row) {
                        row.classList.add("bg-green-50", "dark:bg-green-900/20");
                    }
                    const badge = checkbox.closest("td").querySelector(".inline-flex");
                    if (badge) {
                        badge.className = "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100";
                        badge.textContent = "✓ Sudah";
                    }
                });
                
                const total = checkboxes.length;
                const progressText = btn.closest(".mb-4").querySelector("span");
                if (progressText) {
                    progressText.textContent = 'Progress: ' + total + '/' + total + ' field (100%)';
                }
                
                const progressBar = btn.closest(".mb-4").querySelector(".h-2");
                if (progressBar) {
                    progressBar.className = "bg-green-500 h-2 rounded-full transition-all";
                    progressBar.style.width = "100%";
                }
                
                btn.style.display = "none";
                alert("✓ Semua field berhasil diverifikasi!");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            btn.disabled = false;
            btn.textContent = "✓ Centang Semua";
            alert("Gagal centang semua field. Silakan coba lagi.");
        });
    }
        
    // Fungsi untuk gambar
function openImageModal(imageUrl) {
    const modal = document.getElementById("imageModal");
    const modalImage = document.getElementById("modalImage");
    const imageInfo = document.getElementById("imageInfo");
    
    // Set image source
    modalImage.src = imageUrl;
    
    // Extract filename dari URL untuk info
    const filename = imageUrl.split('/').pop();
    imageInfo.textContent = 'Preview: ' + filename + ' • Scroll untuk zoom';
    
    // Show modal
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
    
    // Set download button function
    window.downloadImage = function() {
        const link = document.createElement('a');
        link.href = imageUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };
}
    
    function closeImageModal() {
        document.getElementById("imageModal").classList.add("hidden");
        document.body.style.overflow = "auto";
    }
    
    // ✅ FUNGSI UNTUK PDF (FIXED)
    function previewPdf(pdfUrl, filename) {
        // Set iframe source
        document.getElementById("pdfIframe").src = pdfUrl;
        document.getElementById("pdfModalTitle").textContent = "Preview: " + filename;
        
        // Set download button
        const downloadBtn = document.getElementById("pdfDownloadBtn");
        downloadBtn.href = pdfUrl;
        downloadBtn.download = filename;
        
        // Show modal
        document.getElementById("pdfModal").classList.remove("hidden");
        document.body.style.overflow = "hidden";
    }
    
    function closePdfModal() {
        document.getElementById("pdfModal").classList.add("hidden");
        document.getElementById("pdfIframe").src = "";
        document.body.style.overflow = "auto";
    }
    
    // ESC key handler
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            closeImageModal();
            closePdfModal();
        }
    });
</script>
JS;

                            return new HtmlString($html);
                        }),
                ]),
        ]);
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

                // Button: Kirim ke Data Progres (untuk OPD perbaiki)
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
                    ->modalHeading('Kirim ke Data Progres')
                    ->modalDescription(
                        fn($record) =>
                        'Data rombongan "' . $record->nama_rombongan . '" belum lengkap (' .
                            $record->getVerificationProgress()['percentage'] . '%). ' .
                            'Data akan dikirim ke OPD untuk diperbaiki.'
                    )
                    ->modalSubmitActionLabel('Ya, Kirim ke OPD')
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
                            ->title('Berhasil Dikirim ke Data Progres OPD')
                            ->body('Rombongan "' . $record->nama_rombongan . '" telah dikirim ke Data Progres untuk diperbaiki OPD.')
                            ->success()
                            ->send();
                    }),

                // Button: Kirim ke Data Sudah Progres (data selesai 100%)
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
                    ->modalHeading('Kirim ke Data Sudah Progres')
                    ->modalDescription(
                        fn($record) =>
                        'Data rombongan "' . $record->nama_rombongan . '" sudah diverifikasi 100%. ' .
                            'Data akan dikirim ke halaman Data Sudah Progres OPD.'
                    )
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
                            ->body('Rombongan "' . $record->nama_rombongan . '" telah dikirim ke Data Sudah Progres OPD.')
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

    public static function getEloquentQuery(): Builder
    {
        // ✅ Jangan pakai parent, langsung from model
        return Rombongan::query()
            ->withoutGlobalScope('opd_filter') // ❗ PENTING: Bypass global scope OPD
            ->where('status_pengiriman', 'Terkirim ke Verifikator')
            ->orderBy('tanggal_masuk_verifikator', 'desc');
    }
}
