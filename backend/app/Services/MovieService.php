<?php

namespace App\Services;

use App\Jobs\ProcessVideoFile;
use App\Models\Movie;
use App\Models\FileUpload;
use App\Http\Controllers\ThumbnailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MovieService
{
    /**
     * Get all movies
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllMovies()
    {
        return Movie::all();
    }

    /**
     * Get movie by ID
     *
     * @param int $id
     * @return Movie
     */
    public function getMovie($id)
    {
        return Movie::findOrFail($id);
    }

    /**
     * Handle video upload from request
     *
     * @param Request $request
     * @return string|null Path to the video file or null if no valid video
     */
    public function handleVideoUpload(Request $request)
    {
        // Handle regular file upload if present
        if ($request->hasFile('video_file')) {
            return $request->file('video_file')->store('videos', 'public');
        } 
        // Handle chunked upload reference
        else if ($request->has('uuid')) {
            $fileUpload = FileUpload::where('uuid', $request->uuid)
                               ->where('status', 'completed')
                               ->first();
            
            if (!$fileUpload) {
                return null;
            }
            
            return $fileUpload->final_path;
        }

        return null;
    }

    /**
     * Create new movie
     *
     * @param array $data
     * @param string $videoPath
     * @return Movie
     */
    public function createMovie(array $data, string $videoPath)
    {
        $movie = Movie::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'date_added' => $data['date_added'],
            'video_file' => $videoPath,
            'is_processed' => false,
        ]);

        // Dispatch job to process the video
        ProcessVideoFile::dispatch($movie);
        
        // Generate thumbnail
        $this->generateThumbnail($movie->id);

        return $movie;
    }

    /**
     * Update existing movie
     *
     * @param Movie $movie
     * @param array $data
     * @param string|null $videoPath
     * @return Movie
     */
    public function updateMovie(Movie $movie, array $data, ?string $videoPath = null)
    {
        $updateVideo = ($videoPath !== null);

        if ($updateVideo) {
            $this->cleanupMovieFiles($movie);
            
            $movie->video_file = $videoPath;
            $movie->is_processed = false;
            $movie->thumbnail = null;
            $movie->hls_path = null;
        }

        // Update movie details
        $movie->title = $data['title'] ?? $movie->title;
        $movie->description = $data['description'] ?? $movie->description;
        $movie->date_added = $data['date_added'] ?? $movie->date_added;
        $movie->save();

        // If video was updated, process it again
        if ($updateVideo) {
            ProcessVideoFile::dispatch($movie);
            $this->generateThumbnail($movie->id);
        }

        return $movie;
    }

    /**
     * Delete movie and its associated files
     *
     * @param int $id
     * @return bool
     */
    public function deleteMovie($id)
    {
        $movie = Movie::findOrFail($id);
        $this->cleanupMovieFiles($movie);
        $movie->delete();
        
        return true;
    }

    /**
     * Clean up movie files (video, thumbnail, HLS)
     *
     * @param Movie $movie
     * @return void
     */
    private function cleanupMovieFiles(Movie $movie)
    {
        if ($movie->video_file) {
            Storage::disk('public')->delete($movie->video_file);
        }
        if ($movie->thumbnail) {
            Storage::disk('public')->delete($movie->thumbnail);
        }
        if ($movie->hls_path) {
            // Delete HLS directory
            $hlsDir = dirname($movie->hls_path);
            Storage::disk('public')->deleteDirectory($hlsDir);
        }
    }

    /**
     * Generate thumbnail for a movie
     *
     * @param int $movieId
     * @return void
     */
    private function generateThumbnail($movieId)
    {
        try {
            app(ThumbnailController::class)->generate($movieId);
        } catch (\Exception $e) {
            Log::error("Failed to generate thumbnail for movie {$movieId}: " . $e->getMessage());
        }
    }
}