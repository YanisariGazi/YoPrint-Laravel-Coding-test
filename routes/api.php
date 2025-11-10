<?php

use App\Http\Controllers\UploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('uploads/list', [UploadController::class, 'list']);
Route::apiResource('uploads', UploadController::class)->only('store', 'show');
