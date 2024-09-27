<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PCController;
use App\Http\Controllers\PartController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/pc/{id}', [PCController::class, 'index'])->name('pc.index');
Route::get('/part', [PartController::class, 'show'])->name('part.show');
Route::get('part/autocomplete', [PartController::class, 'autocomplete'])->name('part.autocomplete');
Route::get('/part/{model}', [PartController::class, 'showModel'])->name('part.model');
Route::post('/add-part-to-pc', [PCController::class, 'addPartToPC'])->name('part.addToPC');
Route::post('/pc/save', [PCController::class, 'save'])->name('pc.save');
Route::post('/remove-part', [PCController::class, 'removePartFromPC'])->name('removePartFromPC');
