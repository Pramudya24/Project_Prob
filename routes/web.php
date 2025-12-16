<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Filament\Verifikator\Resources\RombonganVerifikatorResource\Pages\EditRombonganVerifikator;
use App\Models\RombonganItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\SaveRombonganItemController;


Route::get('/', function () {
    // Redirect ke login jika belum login
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    // Redirect ke panel sesuai role
    $user = auth()->user();

    if ($user->hasRole('opd')) {
        return redirect('/opd');
    }

    if ($user->hasRole('verifikator')) {
        return redirect('/verifikator');
    }

    if ($user->hasRole('monitoring')) {
        return redirect('/monitoring');
    }

    return redirect('/opd');
});

Route::get('/dashboard', function () {
    // Redirect ke panel sesuai role
    $user = auth()->user();

    if ($user->hasRole('opd')) {
        return redirect('/opd');
    }

    if ($user->hasRole('verifikator')) {
        return redirect('/verifikator');
    }

    if ($user->hasRole('monitoring')) {
        return redirect('/monitoring');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/verifikator/rombongan-verifikators/verify-field', function () {
    $data = request()->validate([
        'rombongan_item_id' => 'required|exists:rombongan_items,id',
        'field_name' => 'required|string',
        'is_verified' => 'required|boolean',
    ]);

    $rombonganItem = RombonganItem::find($data['rombongan_item_id']);
    
    if (!$rombonganItem) {
        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
    }

    $verification = $rombonganItem->getOrCreateFieldVerification($data['field_name']);
    
    $verification->update([
        'is_verified' => $data['is_verified'],
        'verified_at' => $data['is_verified'] ? now() : null,
        'verified_by' => $data['is_verified'] ? auth()->id() : null,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Verifikasi berhasil disimpan'
    ]);
})->middleware('auth')->name('verifikator.verify-field');

// Route untuk save catatan per field
Route::post('/verifikator/rombongan-verifikators/save-catatan', function () {
    $data = request()->validate([
        'rombongan_item_id' => 'required|exists:rombongan_items,id',
        'field_name' => 'required|string',
        'catatan' => 'nullable|string',
    ]);

    $rombonganItem = RombonganItem::find($data['rombongan_item_id']);
    
    if (!$rombonganItem) {
        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
    }

    $verification = $rombonganItem->getOrCreateFieldVerification($data['field_name']);
    
    $verification->update([
        'keterangan' => $data['catatan'],
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Catatan berhasil disimpan'
    ]);
})->middleware('auth')->name('verifikator.save-catatan');

// Route untuk verify all fields dalam 1 item
Route::post('/verifikator/rombongan-verifikators/verify-all-fields', function () {
    $data = request()->validate([
        'rombongan_item_id' => 'required|exists:rombongan_items,id',
    ]);

    $rombonganItem = RombonganItem::find($data['rombongan_item_id']);
    
    if (!$rombonganItem) {
        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
    }

    // Get semua field yang perlu diverifikasi
    $fields = $rombonganItem->getVerifiableFields();
    
    // Centang semua field
    foreach ($fields as $fieldName) {
        $verification = $rombonganItem->getOrCreateFieldVerification($fieldName);
        
        $verification->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Semua field berhasil diverifikasi'
    ]);
})->middleware('auth')->name('verifikator.verify-all-fields');

// ✅ TAMBAHKAN INI DI web.php - ROUTE YANG LEBIH ROBUST

Route::get('/private/{path}', function ($path) {
    try {
        // Decode path dari URL encoding
        $decodedPath = urldecode($path);
        
        // Log untuk debugging
        \Log::debug('Private file request:', [
            'raw_path' => $path,
            'decoded_path' => $decodedPath,
            'full_path' => storage_path('app/private/' . $decodedPath),
        ]);
        
        // Cek file exists
        if (!Storage::disk('private')->exists($decodedPath)) {
            \Log::warning('File not found:', [
                'requested_path' => $decodedPath,
                'storage_path' => storage_path('app/private/' . $decodedPath),
                'available_files' => Storage::disk('private')->allFiles(),
            ]);
            
            // Return placeholder SVG untuk file tidak ditemukan
            $svg = <<<'SVG'
<svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#fee;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#fcc;stop-opacity:1" />
        </linearGradient>
    </defs>
    <rect width="100%" height="100%" fill="url(#bg)"/>
    <text x="50%" y="40%" text-anchor="middle" dy=".3em" fill="#c00" font-size="20" font-weight="bold" font-family="system-ui">
        ⚠️ File Tidak Ditemukan
    </text>
    <text x="50%" y="55%" text-anchor="middle" dy=".3em" fill="#666" font-size="12" font-family="monospace">
SVG;
            $svg .= htmlspecialchars(basename($decodedPath));
            $svg .= <<<'SVG'
    </text>
    <text x="50%" y="65%" text-anchor="middle" dy=".3em" fill="#999" font-size="10">
        Silakan upload ulang file ini
    </text>
</svg>
SVG;
            
            return response($svg, 404)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Cache-Control', 'no-cache');
        }
        
        // Get file content
        $fileContent = Storage::disk('private')->get($decodedPath);
        $mimeType = Storage::disk('private')->mimeType($decodedPath);
        $fileSize = Storage::disk('private')->size($decodedPath);
        
        // Log successful access
        \Log::info('File served successfully:', [
            'path' => $decodedPath,
            'mime_type' => $mimeType,
            'size' => $fileSize,
        ]);
        
        // Determine content disposition
        $filename = basename($decodedPath);
        $disposition = 'inline'; // Default inline untuk preview
        
        // Force download untuk file besar (>10MB)
        if ($fileSize > 10 * 1024 * 1024) {
            $disposition = 'attachment';
        }
        
        // Set headers
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Content-Length' => $fileSize,
            'Cache-Control' => 'public, max-age=604800', // Cache 1 minggu
            'X-Content-Type-Options' => 'nosniff',
        ];
        
        return response($fileContent, 200, $headers);
        
    } catch (\Exception $e) {
        \Log::error('Error serving private file:', [
            'path' => $path ?? 'unknown',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        // Return error SVG
        $svg = <<<'SVG'
<svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="#fee"/>
    <text x="50%" y="45%" text-anchor="middle" dy=".3em" fill="#c00" font-size="18" font-weight="bold">
        ❌ Error Loading File
    </text>
    <text x="50%" y="55%" text-anchor="middle" dy=".3em" fill="#666" font-size="12">
SVG;
        $svg .= htmlspecialchars(substr($e->getMessage(), 0, 50));
        $svg .= <<<'SVG'
    </text>
</svg>
SVG;
        
        return response($svg, 500)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'no-cache');
    }
})
->where('path', '.*')
->middleware(['auth'])
->name('private.file');


// ✅ ROUTE UNTUK DEBUG - HAPUS SETELAH TESTING
Route::get('/debug/storage-files', function () {
    if (!auth()->check() || !auth()->user()->hasRole(['verifikator', 'super_admin'])) {
        abort(403);
    }
    
    $files = Storage::disk('private')->allFiles();
    
    return response()->json([
        'total_files' => count($files),
        'files' => collect($files)->map(function ($file) {
            return [
                'path' => $file,
                'exists' => Storage::disk('private')->exists($file),
                'size' => Storage::disk('private')->size($file),
                'mime_type' => Storage::disk('private')->mimeType($file),
                'url' => route('private.file', ['path' => urlencode($file)]),
            ];
        })->values(),
    ]);
})->middleware(['auth']);
require __DIR__ . '/auth.php';

Route::middleware(['auth', 'role:opd'])->prefix('opd')->group(function () {
    Route::post('/rombongan-items/update-field', [SaveRombonganItemController::class, 'updateField'])
        ->name('opd.rombongan-items.update-field');
    
    Route::post('/rombongan-items/bulk-update', [SaveRombonganItemController::class, 'bulkUpdate'])
        ->name('opd.rombongan-items.bulk-update');
});
