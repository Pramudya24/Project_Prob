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
    // Decode path
    $path = urldecode($path);
    
    // Path lengkap ke file di storage
    $fullPath = storage_path('app/private/' . $path);
    
    // Cek apakah file exists
    if (!file_exists($fullPath)) {
        \Log::error('File not found:', ['path' => $fullPath, 'requested' => $path]);
        abort(404, "File tidak ditemukan: $path");
    }
    
    // Get extension
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    
    // Mime types untuk preview
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'bmp' => 'image/bmp',
    ];
    
    // Set headers
    $headers = [
        'Content-Type' => $mimeTypes[$extension] ?? mime_content_type($fullPath),
        'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        'Cache-Control' => 'public, max-age=604800',
    ];
    
    return response()->file($fullPath, $headers);
})->where('path', '.*')  // ✅ TERIMA SEMUA PATH (termasuk subfolder)
    ->middleware(['auth'])
    ->name('private.file');
require __DIR__ . '/auth.php';

Route::middleware(['auth', 'role:opd'])->prefix('opd')->group(function () {
    Route::post('/rombongan-items/update-field', [SaveRombonganItemController::class, 'updateField'])
        ->name('opd.rombongan-items.update-field');
    
    Route::post('/rombongan-items/bulk-update', [SaveRombonganItemController::class, 'bulkUpdate'])
        ->name('opd.rombongan-items.bulk-update');
});
