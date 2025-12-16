<?php
// emergency-fix.php
echo "🚨 EMERGENCY FIX: Hapus preserveFilenames() dari \$get()...\n\n";

$files = [
    'app/Filament/Opd/Resources/EpurcasingResource.php',
    'app/Filament/Opd/Resources/NonTenderResource.php',
    'app/Filament/Opd/Resources/PengadaanDaruratResource.php',
    'app/Filament/Opd/Resources/Pls/PlResource.php',
    'app/Filament/Opd/Resources/Rombongan/Pages/RombonganItemsTable.php',
    'app/Filament/Opd/Resources/SwakelolaResource.php',
    'app/Filament/Opd/Resources/TenderResource.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $original = $content;
    
    // FIX: Hapus ->preserveFilenames() dari $get() calls
    $content = str_replace(
        "\$get('pdn_tkdn_impor')->preserveFilenames()",
        "\$get('pdn_tkdn_impor')",
        $content
    );
    
    // Juga hapus dari schema arrays
    $content = preg_replace(
        "/\\]->preserveFilenames\\(\\)/",
        "]",
        $content
    );
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "✅ Fixed: " . basename($file) . "\n";
    }
}

echo "\n🎉 EMERGENCY FIX APPLIED!\n";
echo "⚠️  Tapi mungkin masih ada error lain...\n";