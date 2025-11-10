<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::resource('/', UploadController::class)->only('index');
