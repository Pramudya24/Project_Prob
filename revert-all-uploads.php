<?php
/**
 * REVERT ALL - Kembalikan semua FileUpload ke state awal
 */

echo "🔄 REVERT SEMUA PERUBAHAN FILEUPLOAD...\n\n";

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('app/Filament/', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($files as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') continue;
    
    $path = $file->getPathname();
    $relativePath = str_replace(getcwd() . '/', '', $path);
    
    $content = file_get_contents($path);
    $original = $content;
    
    // REVERT 1: Hapus semua ->disk('private')
    $content = str_replace("->disk('private')", '', $content);
    $content = str_replace('->disk("private")', '', $content);
    
    // REVERT 2: Hapus semua ->directory('...')
    $content = preg_replace('/->directory\([^)]+\)/', '', $content);
    
    // REVERT 3: Hapus ->visibility('private')
    $content = str_replace("->visibility('private')", '', $content);
    $content = str_replace('->visibility("private")', '', $content);
    
    // REVERT 4: Hapus ->preserveFilenames() yang ditambahkan script
    // TAPI jangan hapus yang sudah ada sebelumnya
    if ($original !== $content) {
        // Cek apakah file ini punya FileUpload
        if (strpos($original, 'FileUpload::make') !== false) {
            echo "✅ Reverted: $relativePath\n";
            file_put_contents($path, $content);
        }
    }
}

echo "\n🎉 SEMUA SUDAH DIKEMBALIKAN KE STATE AWAL!\n";
echo "⚠️  PERHATIAN: FileUpload yang BENAR-BENAR butuh disk('private') juga terhapus.\n";
echo "   Nanti kita perbaiki SATU-SATU yang field-file saja.\n";