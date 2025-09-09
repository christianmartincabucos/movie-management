<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function stream($filename)
    {
        // If no specific filename is provided, extract it from the path
        if (str_contains($filename, '/')) {
            $parts = explode('/', $filename);
            $filename = end($parts);
        }
        
        // Search for the video file in the videos directory
        $path = 'videos/' . $filename;
        
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Video file not found'], 404);
        }
        
        $file = Storage::disk('public')->path($path);
        $size = filesize($file);
        $mime = Storage::disk('public')->mimeType($path);

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => $size,
            'Accept-Ranges' => 'bytes',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept, Range',
        ];

        // Support for range requests (important for video seeking)
        if (request()->header('Range')) {
            $range = request()->header('Range');
            $range = str_replace('bytes=', '', $range);
            $range = explode('-', $range);
            
            $start = (int) $range[0];
            $end = isset($range[1]) && !empty($range[1]) ? (int) $range[1] : $size - 1;
            
            $length = $end - $start + 1;
            
            $headers['Content-Length'] = $length;
            $headers['Content-Range'] = "bytes $start-$end/$size";
            
            return response()->stream(function () use ($file, $start, $length) {
                $handle = fopen($file, 'rb');
                fseek($handle, $start);
                echo fread($handle, $length);
                fclose($handle);
            }, 206, $headers);
        }

        return response()->stream(function () use ($file) {
            readfile($file);
        }, 200, $headers);
    }
}
