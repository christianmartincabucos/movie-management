<?php

namespace App\Jobs;

use App\Models\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Coordinate\Dimension;

class ProcessVideoFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;
    
    /**
     * The movie instance.
     */
    protected $movie;

    /**
     * Create a new job instance.
     */
    public function __construct(Movie $movie)
    {
        $this->movie = $movie;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',  // Update with your FFmpeg path
            'ffprobe.binaries' => '/usr/bin/ffprobe', // Update with your FFprobe path
            'timeout'          => 3600, // 1 hour
            'ffmpeg.threads'   => 12,   // Use 12 threads
        ]);

        $videoPath = storage_path('app/public/' . $this->movie->video_file);
        $video = $ffmpeg->open($videoPath);

        // 1. Generate thumbnail
        $this->generateThumbnail($this->movie->id);
        
        // 2. Generate HLS playlist for adaptive streaming
        $this->generateHLS($ffmpeg, $videoPath);
        
        // Update movie record with processing status
        $this->movie->is_processed = true;
        $this->movie->save();
        
        Log::info('Video processing completed for movie ID: ' . $this->movie->id);
    }
    
    /**
     * Generate a thumbnail from the video
     */
    protected function generateThumbnail($id)
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
            Log::error("Video file not found: {$videoPath}");
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
            
            $command = "ffmpeg -y -i " . escapeshellarg($videoPath) . " -ss 00:00:03 -frames:v 1 -q:v 2 " . escapeshellarg($thumbnailPath) . " 2>&1";
            
            // Execute the command and capture output
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                // Try a different timestamp if the first attempt failed
                $command = "ffmpeg -y -i " . escapeshellarg($videoPath) . " -ss 00:00:01 -frames:v 1 -q:v 2 " . escapeshellarg($thumbnailPath) . " 2>&1";
                exec($command, $output, $returnVar);
                
                if ($returnVar !== 0) {
                    return response()->json([
                        'error' => 'Failed to generate thumbnail',
                        'details' => implode("\n", $output)
                    ], 500);
                }
            }
            
            // Update the movie record with the thumbnail path
            $movie->thumbnail = $thumbnailFilename;
            $movie->save();
            
            return response()->json([
                'thumbnail' => $movie->thumbnail
            ]);
        } catch (\Exception $e) {
            Log::error("Exception generating thumbnail: " . $e->getMessage());
            return response()->json([
                'error' => 'Exception while generating thumbnail', 
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate HLS playlist and segments for adaptive streaming
     */
    protected function generateHLS($ffmpeg, $videoPath): void
    {
        try {
            // Extract filename without extension
            $filename = pathinfo($this->movie->video_file, PATHINFO_FILENAME);
            $hlsPath = 'hls/' . $filename;
            $fullHlsPath = storage_path('app/public/' . $hlsPath);
            
            // Create directory if it doesn't exist
            if (!file_exists($fullHlsPath)) {
                mkdir($fullHlsPath, 0755, true);
            }
            
            // Define resolutions for adaptive streaming
            $resolutions = [
                ['width' => 640, 'height' => 360, 'bitrate' => 600],  // 360p
                ['width' => 842, 'height' => 480, 'bitrate' => 1000],  // 480p
                ['width' => 1280, 'height' => 720, 'bitrate' => 2500],  // 720p
                ['width' => 1920, 'height' => 1080, 'bitrate' => 4000], // 1080p
            ];
            
            // Generate variants for each resolution
            $playlistPaths = [];
            
            foreach ($resolutions as $resolution) {
                $resPath = $fullHlsPath . '/' . $resolution['height'] . 'p';
                if (!file_exists($resPath)) {
                    mkdir($resPath, 0755, true);
                }
                
                $video = $ffmpeg->open($videoPath);
                
                // Create format with specific bitrate
                $format = new X264('aac', 'libx264');
                $format->setKiloBitrate($resolution['bitrate'])
                       ->setAudioKiloBitrate(128);
                
                // Create playlist for this resolution
                $playlistPath = $resPath . '/playlist.m3u8';
                $video->filters()
                     ->resize(new Dimension($resolution['width'], $resolution['height']))
                     ->synchronize();
                
                $video->save($format, $playlistPath);
                
                $playlistPaths[] = [
                    'path' => $hlsPath . '/' . $resolution['height'] . 'p/playlist.m3u8',
                    'resolution' => $resolution['height'] . 'p'
                ];
            }
            
            // Create master playlist
            $masterPlaylistContent = "#EXTM3U\n#EXT-X-VERSION:3\n";
            foreach ($playlistPaths as $playlist) {
                $masterPlaylistContent .= "#EXT-X-STREAM-INF:BANDWIDTH=" . 
                    $this->getResolutionBandwidth($playlist['resolution']) . 
                    ",RESOLUTION=" . $this->getResolutionDimensions($playlist['resolution']) . "\n" .
                    $playlist['resolution'] . "/playlist.m3u8\n";
            }
            
            file_put_contents($fullHlsPath . '/master.m3u8', $masterPlaylistContent);
            
            // Update movie with HLS path
            $this->movie->hls_path = $hlsPath . '/master.m3u8';
            $this->movie->save();
            
            Log::info('HLS playlist generated for movie ID: ' . $this->movie->id);
        } catch (\Exception $e) {
            Log::error('HLS generation failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get bandwidth for resolution
     */
    private function getResolutionBandwidth($resolution): int
    {
        switch ($resolution) {
            case '360p': return 600000;
            case '480p': return 1000000;
            case '720p': return 2500000;
            case '1080p': return 4000000;
            default: return 1000000;
        }
    }
    
    /**
     * Get dimensions for resolution
     */
    private function getResolutionDimensions($resolution): string
    {
        switch ($resolution) {
            case '360p': return '640x360';
            case '480p': return '842x480';
            case '720p': return '1280x720';
            case '1080p': return '1920x1080';
            default: return '1280x720';
        }
    }
    
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Video processing failed for movie ID: ' . $this->movie->id . '. Error: ' . $exception->getMessage());
        
        // Update movie status to indicate processing failure
        $this->movie->processing_error = $exception->getMessage();
        $this->movie->save();
    }
}