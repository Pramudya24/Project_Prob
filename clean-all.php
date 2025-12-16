<?php
// clean-all.php - Hapus SEMUA modifikasi script sebelumnya
echo "🧹 CLEAN ALL MODIFICATIONS...\n\n";

$patterns = [
    // Hapus disk('private') kecuali untuk FileUpload field-file
    "/->disk\\(['\"]private['\"]\\)/" => '',
    
    // Hapus semua directory()
    "/->directory\\([^)]+\\)/" => '',
    
    // Hapus visibility('private')
    "/->visibility\\(['\"]private['\"]\\)/" => '',
    
    // Hapus preserveFilenames() dari NON-FileUpload
    // Tapi jangan hapus dari FileUpload yang asli
];

$dir = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('app/Filament/', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($dir as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') continue;
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Hanya proses jika ada FileUpload
    if (strpos($content, 'FileUpload::make') !== false) {
        // Identifikasi field-file yang BENAR
        $fileFields = ['BAST', 'summary_report', 'bast_document', 'surat_pesanan', 'realisasi'];
        
        foreach ($fileFields as $field) {
            // Jangan hapus dari field-file ini (nanti kita perbaiki manual)
            // Pattern: FileUpload::make('field_name').......
            $pattern = "/FileUpload::make\\(['\"]" . $field . "['\"]\\)/";
            if (preg_match($pattern, $content)) {
                // Skip file ini dari cleaning
                continue 2;
            }
        }
        
        // Clean file yang TIDAK punya field-file
        $content = preg_replace("/->disk\\(['\"]private['\"]\\)/", '', $content);
        $content = preg_replace("/->directory\\([^)]+\\)/", '', $content);
        $content = preg_replace("/->visibility\\(['\"]private['\"]\\)/", '', $content);
        
        // Hapus preserveFilenames() tambahan (tapi bukan yang asli)
        $content = preg_replace("/(?<!FileUpload::make\([^)]+\))->preserveFilenames\\(\\)/", '', $content);
        
        if ($content !== $original) {
            echo "✅ Cleaned: " . basename($file) . "\n";
            file_put_contents($file, $content);
        }
    }
}

echo "\n✨ SEMUA DIREVERT, kecuali field-file penting!\n";
echo "🔥 Sekarang perbaiki MANUAL field-file berikut:\n";
echo "   1. BAST\n";
echo "   2. summary_report\n";
echo "   3. bast_document\n";
echo "   4. surat_pesanan\n";
echo "   5. realisasi\n";