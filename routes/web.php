<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
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
require __DIR__ . '/auth.php';
