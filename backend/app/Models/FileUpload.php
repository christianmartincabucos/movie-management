<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'original_filename',
        'total_chunks',
        'chunks_received',
        'status',
        'final_path'
    ];
}