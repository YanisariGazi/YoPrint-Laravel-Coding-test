<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    protected $table = 'file_uploads';
    protected $fillable = [
        'filename',
        'path',
        'checksum',
        'status',
        'message',
        'error',
        'processed_at',
        'finished_at',
    ];
    protected $casts = [
        'processed_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
