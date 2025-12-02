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

Route::post('/verifikator/rombongan-verifikators/save-field-note', 
    [EditRombonganVerifikator::class, 'saveFieldNote']
)->name('verifikator.save-field-note')->middleware(['auth']);

Route::post('/verifikator/rombongan-verifikators/verify-field', 
    [EditRombonganVerifikator::class, 'verifyField']
)->name('verifikator.verify-field')->middleware(['auth']);

/**
 * Route untuk serve file dari storage/app/private
 * Format URL: /private-file/realisasi/nama-file.jpg
 */
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
