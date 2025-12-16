<?php
/**
 * FIX ERROR: Call to a member function preserveFilenames() on string
 */

echo "🔧 MEMPERBAIKI ERROR preserveFilenames()...\n\n";

// Field yang BUKAN FileUpload (ini biasanya text/select/number)
$nonFileComponents = [
    'TextInput::make',
    'Select::make', 
    'Textarea::make',
    'DatePicker::make',
    'DateTimePicker::make',
    'Toggle::make',
    'Checkbox::make',
    'Radio::make',
    'RichEditor::make',
    'MarkdownEditor::make',
    'ColorPicker::make',
    'TagsInput::make',
];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('app/Filament/', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($files as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') continue;
    
    $content = file_get_contents($file);
    $original = $content;
    
    foreach ($nonFileComponents as $component) {
        // Pattern: Component::make('field_name')->preserveFilenames()
        $pattern = '/(' . preg_quote($component, '/') . '\([^)]+\))->preserveFilenames\(\)/';
        
        if (preg_match($pattern, $content)) {
            // Hapus ->preserveFilenames() dari component ini
            $content = preg_replace($pattern, '$1', $content);
            echo "✅ Fixed: " . basename($file) . " - removed preserveFilenames() from {$component}\n";
        }
        
        // Juga hapus disk() dan directory() dari non-file components
        $content = preg_replace(
            '/(' . preg_quote($component, '/') . '\([^)]+\))->disk\([^)]+\)/',
            '$1',
            $content
        );
        
        $content = preg_replace(
            '/(' . preg_quote($component, '/') . '\([^)]+\))->directory\([^)]+\)/',
            '$1',
            $content
        );
    }
    
    if ($content !== $original) {
        file_put_contents($file, $content);
    }
}

echo "\n🎉 ERROR preserveFilenames() FIXED!\n";