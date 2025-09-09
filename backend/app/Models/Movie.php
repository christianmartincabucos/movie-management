<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'date_added',
        'video_file',
        'thumbnail',
        'hls_path',
        'is_processed',
        'processing_error'
    ];

    protected $casts = [
        'date_added' => 'date',
        'is_processed' => 'boolean',
    ];
}