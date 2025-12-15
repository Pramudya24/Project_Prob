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

Route::get('/private/{path}', function ($path) {
    try {
        // Decode path
        $path = urldecode($path);
        
        // Cek file exists pakai Storage facade (lebih aman)
        if (!Storage::disk('private')->exists($path)) {
            \Log::error('File not found:', [
                'path' => storage_path('app/private/' . $path),
                'requested' => $path
            ]);
            
            // Return placeholder SVG instead of abort
            $svg = '<svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">
                <rect width="100%" height="100%" fill="#f3f4f6"/>
                <text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#9ca3af" font-size="18" font-family="system-ui">
                    File Tidak Ditemukan
                </text>
                <text x="50%" y="60%" text-anchor="middle" dy=".3em" fill="#d1d5db" font-size="12" font-family="monospace">
                    ' . htmlspecialchars(basename($path)) . '
                </text>
            </svg>';
            
            return response($svg, 404)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Cache-Control', 'no-cache');
        }
        
        // Get file content dan mime type
        $file = Storage::disk('private')->get($path);
        $mimeType = Storage::disk('private')->mimeType($path);
        
        // Set headers
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control' => 'public, max-age=604800', // Cache 1 minggu
        ];
        
        return response($file, 200, $headers);
        
    } catch (\Exception $e) {
        \Log::error('Error serving private file:', [
            'path' => $path,
            'error' => $e->getMessage()
        ]);
        
        // Return error placeholder
        $svg = '<svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">
            <rect width="100%" height="100%" fill="#fee"/>
            <text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#c00" font-size="18">
                Error: ' . htmlspecialchars($e->getMessage()) . '
            </text>
        </svg>';
        
        return response($svg, 500)
            ->header('Content-Type', 'image/svg+xml');
    }
})->where('path', '.*')
    ->middleware(['auth'])
    ->name('private.file');
require __DIR__ . '/auth.php';

Route::middleware(['auth', 'role:opd'])->prefix('opd')->group(function () {
    Route::post('/rombongan-items/update-field', [SaveRombonganItemController::class, 'updateField'])
        ->name('opd.rombongan-items.update-field');
    
    Route::post('/rombongan-items/bulk-update', [SaveRombonganItemController::class, 'bulkUpdate'])
        ->name('opd.rombongan-items.bulk-update');
});
