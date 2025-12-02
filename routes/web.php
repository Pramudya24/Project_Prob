<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Filament\Verifikator\Resources\RombonganVerifikatorResource\Pages\EditRombonganVerifikator;
use App\Models\RombonganItem;


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

    return redirect('/dashboard');
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

Route::get('/private-file/{path}', function ($path) {
    $filePath = storage_path('app/private/' . $path);
    
    // Cek apakah file exists
    if (!file_exists($filePath)) {
        abort(404, 'File not found');
    }
    
    // Get mime type
    $mimeType = mime_content_type($filePath);
    
    // Return file dengan headers yang benar
    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000', // Cache 1 tahun
    ]);
})->where('path', '.*')->name('private.file');
require __DIR__ . '/auth.php';
