<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ThumbnailController extends Controller
{
    public function generate($id)
    {
        $movie = Movie::findOrFail($id);
        
        // Skip if the movie already has a thumbnail
        if ($movie->thumbnail) {
            return response()->json([
                'thumbnail' => $movie->thumbnail
            ]);
        }
        
        // Check if the video exists
        $videoPath = storage_path('app/public/' . $movie->video_file);
        if (!file_exists($videoPath)) {
            return response()->json(['error' => 'Video file not found'], 404);
        }
        try {
            // Generate a thumbnail using FFmpeg (make sure FFmpeg is installed on your server)
            $thumbnailFilename = 'thumbnails/' . pathinfo($movie->video_file, PATHINFO_FILENAME) . '.jpg';
            $thumbnailPath = storage_path('app/public/' . $thumbnailFilename);
            
            // Create thumbnails directory if it doesn't exist
            $thumbnailDir = dirname($thumbnailPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            
            // Use FFmpeg to extract a frame from 3 seconds into the video
            $command = "ffmpeg -i {$videoPath} -ss 00:00:03 -frames:v 1 -q:v 2 {$thumbnailPath}";
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                return response()->json(['error' => 'Failed to generate thumbnail'], 500);
            }
            
            // Update the movie record with the thumbnail path
            $movie->thumbnail = $thumbnailFilename;
            $movie->save();
            
            return response()->json([
                'thumbnail' => $movie->thumbnail
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function serve($filename)
    {
        $path = 'thumbnails/' . $filename;
        
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Thumbnail not found'], 404);
        }
        
        $file = Storage::disk('public')->get($path);
        $type = Storage::disk('public')->mimeType($path);
        
        $response = response($file, 200);
        $response->header('Content-Type', $type);
        $response->header('Access-Control-Allow-Origin', '*');
        
        return $response;
    }
}