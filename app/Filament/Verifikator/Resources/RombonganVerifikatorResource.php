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

                                    // Sub-header nama item dengan button centang semua
                                    $html .= '<div class="mb-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">';
                                    $html .= '<div class="flex justify-between items-center mb-2">';
                                    $html .= '<h4 class="text-lg font-semibold">Item: ' . htmlspecialchars($item['nama_pekerjaan']) . '</h4>';
                                    $html .= '<div class="flex items-center gap-3">';

                                    // Button Centang Semua
                                    if ($progress['percentage'] < 100) {
                                        $html .= '<button ';
                                        $html .= 'type="button" ';
                                        $html .= 'onclick="event.preventDefault(); event.stopPropagation(); verifyAllFields(' . $item['rombongan_item_id'] . ')" ';
                                        $html .= 'class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition">';
                                        $html .= '✓ Centang Semua';
                                        $html .= '</button>';
                                    }

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
                                        $fieldLabel = $field['field_label'];
                                        $keterangan = $field['keterangan'] ?? '';

                                        // Tentukan apakah field paten (tidak ada checkbox)
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

                                        // ✅ FIX: Generate URL yang benar untuk file
                                        $isImageField = false;
                                        $isPdfField = false;
                                        $fileUrl = null;
                                        $fileExists = false;

                                        if ($fieldValue && $fieldValue !== '-') {
                                            $cleanFilename = trim($fieldValue);
                                            
                                            // Cek apakah ini PDF
                                            $isPdfField = str_ends_with(strtolower($cleanFilename), '.pdf');

                                            // Cek apakah ini gambar
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

                                            // Generate URL jika file adalah gambar atau PDF
                                            if ($isPdfField || $isImageField) {
                                                try {
                                                    // ✅ CEK FILE EXISTS DULU
                                                    $fileExists = Storage::disk('private')->exists($cleanFilename);
                                                    
                                                    if ($fileExists) {
                                                        // ✅ ENCODE PATH UNTUK URL
                                                        $encodedPath = urlencode($cleanFilename);
                                                        $fileUrl = route('private.file', ['path' => $encodedPath]);
                                                    } else {
                                                        \Log::warning('File not found for field', [
                                                            'field_name' => $fieldName,
                                                            'path' => $cleanFilename,
                                                            'full_path' => Storage::disk('private')->path($cleanFilename),
                                                        ]);
                                                    }
                                                } catch (\Exception $e) {
                                                    \Log::error('Error generating file URL:', [
                                                        'field_value' => $fieldValue,
                                                        'error' => $e->getMessage()
                                                    ]);
                                                }
                                            }
                                        }

                                        // Tentukan row class
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

                                        if ($isPdfField) {
                                            if ($fileExists && $fileUrl) {
                                                // ✅ PREVIEW PDF DENGAN FILE YANG ADA
                                                $html .= '<div class="space-y-3">';
                                                $html .= '<div class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/30 transition cursor-pointer" onclick="event.preventDefault(); event.stopPropagation(); previewPdf(\'' . addslashes($fileUrl) . '\', \'' . addslashes(htmlspecialchars(basename($cleanFilename))) . '\')">';
                                                $html .= '<div class="flex-shrink-0 p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">';
                                                $html .= '<svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>';
                                                $html .= '</div>';
                                                $html .= '<div class="flex-1 min-w-0">';
                                                $html .= '<div class="font-medium text-gray-900 dark:text-gray-100 truncate">' . htmlspecialchars(basename($cleanFilename)) . '</div>';
                                                $html .= '<div class="text-sm text-green-600 dark:text-green-400">✓ File tersedia • Klik untuk preview</div>';
                                                $html .= '</div>';
                                                $html .= '</div>';

                                                $html .= '<div class="flex flex-wrap gap-2">';
                                                // $html .= '<button type="button" onclick="event.preventDefault(); event.stopPropagation(); previewPdf(\'' . addslashes($fileUrl) . '\', \'' . addslashes(htmlspecialchars(basename($cleanFilename))) . '\')" class="px-3 py-1.5 text-sm font-medium bg-blue-600 text-white rounded hover:bg-blue-700 transition inline-flex items-center gap-1">';
                                                // $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
                                                // $html .= 'Preview';
                                                $html .= '</button>';
                                                $html .= '<a href="' . $fileUrl . '" target="_blank" onclick="event.stopPropagation()" class="px-3 py-1.5 text-sm font-medium bg-gray-600 text-white rounded hover:bg-gray-700 transition inline-flex items-center gap-1">';
                                                $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>';
                                                $html .= 'Buka Tab Baru';
                                                $html .= '</a>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                            } else {
                                                // ✅ FILE PDF TIDAK DITEMUKAN
                                                $html .= '<div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">';
                                                $html .= '<div class="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">';
                                                $html .= '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';
                                                $html .= '<div>';
                                                $html .= '<div class="font-medium">File PDF tidak ditemukan</div>';
                                                $html .= '<div class="text-xs mt-1">' . htmlspecialchars($cleanFilename) . '</div>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                            }
                                        } elseif ($isImageField) {
                                            if ($fileExists && $fileUrl) {
                                                // ✅ PREVIEW GAMBAR DENGAN FILE YANG ADA
                                                $html .= '<div class="flex items-center gap-3">';
                                                $html .= '<img src="' . $fileUrl . '" ';
                                                $html .= 'alt="Preview" ';
                                                $html .= 'class="w-32 h-32 object-cover rounded border cursor-pointer hover:opacity-80 transition" ';
                                                $html .= 'onclick="event.preventDefault(); event.stopPropagation(); openImageModal(\'' . addslashes($fileUrl) . '\')" ';
                                                $html .= 'onerror="this.src=\'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23fee\' width=\'100\' height=\'100\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23c00\' font-size=\'12\'%3EError%3C/text%3E%3C/svg%3E\'"';
                                                $html .= '>';
                                                $html .= '<div class="text-xs text-gray-500 dark:text-gray-400">';
                                                $html .= '<a href="' . $fileUrl . '" target="_blank" onclick="event.stopPropagation()" class="text-primary-600 hover:underline">Lihat full</a>';
                                                $html .= '<div class="mt-1 text-green-600 dark:text-green-400">✓ ' . htmlspecialchars(basename($cleanFilename)) . '</div>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                            } else {
                                                // ✅ FILE GAMBAR TIDAK DITEMUKAN
                                                $html .= '<div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">';
                                                $html .= '<div class="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">';
                                                $html .= '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';
                                                $html .= '<div>';
                                                $html .= '<div class="font-medium">Gambar tidak ditemukan</div>';
                                                $html .= '<div class="text-xs mt-1">' . htmlspecialchars($cleanFilename) . '</div>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                            }
                                        } else {
                                            // Text biasa
                                            $html .= htmlspecialchars($fieldValue ?? '-');
                                        }

                                        $html .= '</td>';

                                        // KOLOM VERIFIKASI
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">';

                                        if ($isPatenField) {
                                            $html .= '<div class="flex items-center justify-center gap-2">';
                                            $html .= '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100 border border-gray-300 dark:border-gray-700">';
                                            $html .= '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>';
                                            $html .= 'Paten';
                                            $html .= '</span>';
                                            $html .= '<span class="text-xs text-gray-500">(tidak diverifikasi)</span>';
                                            $html .= '</div>';
                                        } else {
                                            $checkboxId = 'verify_' . $item['rombongan_item_id'] . '_' . $fieldName;
                                            $checked = $isVerified ? 'checked' : '';

                                            $html .= '<div class="flex items-center justify-center gap-3">';
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

                                        // KOLOM CATATAN
                                        $html .= '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3">';

                                        if ($isPatenField) {
                                            $html .= '<div class="text-center">';
                                            $html .= '<span class="text-sm text-gray-500 italic">Field paten - tidak memerlukan catatan</span>';
                                            $html .= '</div>';
                                        } else {
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

                            // ✅ MODAL GAMBAR - FIX ONCLICK
                            $html .= '
                            <div id="imageModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-75 flex items-center justify-center p-4" onclick="closeImageModal()">
                                <div class="max-w-3xl max-h-[85vh] w-full bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-2xl flex flex-col" onclick="event.stopPropagation()">
                                    <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600 flex justify-between items-center">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Preview Gambar</h3>
                                        <div class="flex gap-2">
                                            <button type="button" onclick="window.downloadImage()" class="px-3 py-1.5 text-sm font-medium bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                                ⬇️ Download
                                            </button>
                                            <button type="button" onclick="closeImageModal()" class="px-3 py-1.5 text-sm font-medium bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                                                ✕ Tutup
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-1 overflow-auto p-6 flex items-center justify-center">
                                        <img id="modalImage" 
                                            src="" 
                                            class="max-w-full max-h-[60vh] w-auto h-auto object-contain rounded-lg shadow-lg border border-gray-200 dark:border-gray-700" 
                                            onerror="this.src=\'data:image/svg+xml,%3Csvg width=\'400\' height=\'300\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Crect fill=\'%23fee\' width=\'100%25\' height=\'100%25\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23c00\' font-size=\'16\'%3EGambar tidak dapat dimuat%3C/text%3E%3C/svg%3E\'">
                                    </div>
                                    
                                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-300 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 text-center">
                                        <span id="imageInfo">Klik di luar gambar untuk menutup</span>
                                    </div>
                                </div>
                            </div>';

                            // ✅ MODAL PDF - FIX ONCLICK
                            $html .= '
                            <div id="pdfModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center p-4" onclick="closePdfModal()">
                                <div class="relative w-full max-w-6xl h-[90vh] bg-white dark:bg-gray-800 rounded-lg shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                                    <div class="flex justify-between items-center p-4 bg-gray-100 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" id="pdfModalTitle">Preview PDF</h3>
                                        <div class="flex gap-2">
                                            <a id="pdfDownloadBtn" href="#" download class="px-3 py-1.5 text-sm font-medium bg-blue-600 text-white rounded hover:bg-blue-700 transition inline-flex items-center gap-1">
                                                ⬇️ Download
                                            </a>
                                            <button type="button" onclick="closePdfModal()" class="px-3 py-1.5 text-sm font-medium bg-gray-600 text-white rounded hover:bg-gray-700 transition">Tutup</button>
                                        </div>
                                    </div>
                                    <div class="h-full relative">
                                        <div id="pdfLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800 z-10">
                                            <div class="text-center">
                                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-3"></div>
                                                <p class="text-gray-600 dark:text-gray-400">Loading PDF...</p>
                                            </div>
                                        </div>
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

                            $html .= <<<'JS'
<script>
    // ✅ PREVENT FORM SUBMISSION
    document.addEventListener('DOMContentLoaded', function() {
        // Prevent form submission on button clicks
        document.querySelectorAll('button[type="button"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });
    });

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
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Gagal menyimpan verifikasi. Silakan coba lagi.");
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
    
    // ✅ FUNGSI GAMBAR - FIXED
    function openImageModal(imageUrl) {
        const modal = document.getElementById("imageModal");
        const modalImage = document.getElementById("modalImage");
        const imageInfo = document.getElementById("imageInfo");
        
        modalImage.src = imageUrl;
        
        const filename = imageUrl.split('/').pop();
        imageInfo.textContent = 'Preview: ' + decodeURIComponent(filename);
        
        modal.classList.remove("hidden");
        document.body.style.overflow = "hidden";
        
        window.downloadImage = function() {
            const link = document.createElement('a');
            link.href = imageUrl;
            link.download = decodeURIComponent(filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };
    }
    
    function closeImageModal() {
        document.getElementById("imageModal").classList.add("hidden");
        document.body.style.overflow = "auto";
    }
    
    // ✅ FUNGSI PDF - FIXED
    function previewPdf(pdfUrl, filename) {
        const modal = document.getElementById("pdfModal");
        const iframe = document.getElementById("pdfIframe");
        const loading = document.getElementById("pdfLoading");
        
        // Reset state
        loading.classList.remove("hidden");
        iframe.src = "";
        
        // Set data
        document.getElementById("pdfModalTitle").textContent = "Preview: " + filename;
        document.getElementById("pdfDownloadBtn").href = pdfUrl;
        document.getElementById("pdfDownloadBtn").download = filename;
        
        // Show modal first
        modal.classList.remove("hidden");
        document.body.style.overflow = "hidden";
        
        // Then load PDF
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
                            ->success()
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