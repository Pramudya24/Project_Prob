<?php
/**
 * SCRIPT PERBAIKI OTOMATIS SEMUA FILEUPLOAD
 * 
 * Cara pakai:
 * 1. Simpan file ini di root project (sejajar dengan artisan)
 * 2. Jalankan: php fix-uploads.php
 * 3. Script akan scan dan perbaiki semua FileUpload di Filament
 */

echo "🚀 MULAI PERBAIKAN FILEUPLOAD...\n";
echo "====================================\n\n";

// Mapping field ke folder yang benar
$fieldToDirectory = [
    'BAST' => 'BAST',
    'bast_document' => 'bast-documents',
    'summary_report' => 'summary-reports',
    'surat_pesanan' => 'surat-pesanan',
    'surat_pesanan' => 'surat_pesanan', // dua versi untuk aman
    'realisasi' => 'realisasi',
];

// Folder yang akan discan
$directories = [
    'app/Filament/Opd/Resources/',
    'app/Filament/Verifikator/Resources/',
    'app/Filament/Monitoring/Resources/',
    'app/Filament/',
];

$totalFixed = 0;
$scannedFiles = 0;

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "⚠️  Folder tidak ditemukan: $dir\n";
        continue;
    }
    
    echo "📂 Scanning folder: $dir\n";
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }
        
        $scannedFiles++;
        $filePath = $file->getPathname();
        $relativePath = str_replace(getcwd() . '/', '', $filePath);
        
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        foreach ($fieldToDirectory as $field => $directory) {
            // Pattern untuk mencari FileUpload::make('field_name')
            $patterns = [
                "/FileUpload::make\\(['\"]" . preg_quote($field, '/') . "['\"]\\)/",
                "/FileUpload::make\\(['\"]" . preg_quote($field, '/') . "['\"]\\s*\\)/",
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    // Cari seluruh blok FileUpload sampai titik koma
                    $blockPattern = "/FileUpload::make\\(['\"]" . preg_quote($field, '/') . "['\"]\\)(.*?);/s";
                    
                    if (preg_match($blockPattern, $content, $matches)) {
                        $fullMatch = $matches[0];
                        $config = $matches[1];
                        
                        echo "\n🔍 Ditemukan: {$relativePath}\n";
                        echo "   Field: {$field}\n";
                        
                        $modified = false;
                        $newConfig = $fullMatch;
                        
                        // 1. Cek dan tambahkan disk('private')
                        if (!preg_match('/->disk\([\'"]private[\'"]\)/i', $fullMatch)) {
                            $newConfig = preg_replace(
                                '/FileUpload::make\([\'"]' . preg_quote($field, '/') . '[\'"]\)/',
                                "FileUpload::make('{$field}')->disk('private')",
                                $newConfig
                            );
                            $modified = true;
                            echo "   [+] Ditambah: disk('private')\n";
                        }
                        
                        // 2. Cek dan tambahkan directory()
                        if (!preg_match('/->directory\(/i', $fullMatch)) {
                            // Sisipkan directory() setelah disk() atau di awal config
                            if (strpos($newConfig, '->disk(') !== false) {
                                $newConfig = str_replace(
                                    "->disk('private')",
                                    "->disk('private')->directory('{$directory}')",
                                    $newConfig
                                );
                            } else {
                                $newConfig = preg_replace(
                                    '/FileUpload::make\([\'"]' . preg_quote($field, '/') . '[\'"]\)/',
                                    "FileUpload::make('{$field}')->directory('{$directory}')",
                                    $newConfig
                                );
                            }
                            $modified = true;
                            echo "   [+] Ditambah: directory('{$directory}')\n";
                        }
                        
                        // 3. Cek dan tambahkan visibility('private')
                        if (!preg_match('/->visibility\(/i', $fullMatch)) {
                            // Tambahkan sebelum akhir atau sebelum ;
                            $newConfig = str_replace(');', ")->visibility('private');", $newConfig);
                            $modified = true;
                            echo "   [+] Ditambah: visibility('private')\n";
                        }
                        
                        // 4. Cek dan tambahkan preserveFilenames() jika belum ada
                        if (!preg_match('/->preserveFilenames\(/i', $fullMatch)) {
                            // Sisipkan sebelum visibility atau di akhir
                            if (strpos($newConfig, '->visibility(') !== false) {
                                $newConfig = str_replace(
                                    '->visibility(',
                                    "->preserveFilenames()->visibility(",
                                    $newConfig
                                );
                            } else {
                                $newConfig = str_replace(');', ")->preserveFilenames();", $newConfig);
                            }
                            $modified = true;
                            echo "   [+] Ditambah: preserveFilenames()\n";
                        }
                        
                        if ($modified) {
                            // Replace di content
                            $content = str_replace($fullMatch, $newConfig, $content);
                            $totalFixed++;
                            
                            echo "   ✅ DIPERBAIKI!\n";
                        } else {
                            echo "   ✓ Sudah benar\n";
                        }
                    }
                }
            }
        }
        
        // Simpan perubahan
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "   💾 Disimpan: {$relativePath}\n";
        }
    }
}

echo "\n====================================\n";
echo "📊 HASIL:\n";
echo "   File yang discan: {$scannedFiles}\n";
echo "   Perbaikan dibuat: {$totalFixed}\n";
echo "   Selesai pada: " . date('Y-m-d H:i:s') . "\n";

if ($totalFixed > 0) {
    echo "\n🎉 SEMUA FILEUPLOAD SUDAH DIPERBAIKI!\n";
    echo "   File baru akan disimpan di:\n";
    echo "   - storage/app/private/BAST/\n";
    echo "   - storage/app/private/summary-reports/\n";
    echo "   - storage/app/private/bast-documents/\n";
    echo "   - storage/app/private/surat-pesanan/\n";
    echo "   - dll sesuai field...\n";
    
    echo "\n⚠️  PERHATIAN:\n";
    echo "   1. File yang sudah terupload TIDAK otomatis pindah\n";
    echo "   2. Upload file baru akan ke folder yang benar\n";
    echo "   3. File lama tetap bisa diakses (karena sudah dipindahkan manual)\n";
} else {
    echo "\n🤔 Tidak ada yang perlu diperbaiki.\n";
    echo "   Mungkin semua FileUpload sudah benar,\n";
    echo "   atau field-file tidak ditemukan.\n";
}

echo "\n✨ SELESAI!\n";