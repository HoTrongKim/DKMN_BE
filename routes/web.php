<?php

use App\Http\Controllers\TestMail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/testmail', [TestMail::class, 'testmail'])->name('testmail');
