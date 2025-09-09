<?php

namespace App\Services;

use App\Models\Movie;
use Illuminate\Support\Facades\Storage;

class MovieService
{
    public function getAllMovies()
    {
        return Movie::all();
    }

    public function getMovieById($id)
    {
        return Movie::findOrFail($id);
    }

    public function createMovie($data)
    {
        $movie = new Movie();
        $movie->title = $data['title'];
        $movie->description = $data['description'];
        $movie->date_added = now();

        if (isset($data['video_file'])) {
            $movie->video_file = $data['video_file']->store('videos', 'public');
        }

        $movie->save();
        return $movie;
    }

    public function updateMovie($id, $data)
    {
        $movie = Movie::findOrFail($id);
        $movie->title = $data['title'];
        $movie->description = $data['description'];

        if (isset($data['video_file'])) {
            // Delete the old video file if it exists
            if ($movie->video_file) {
                Storage::disk('public')->delete($movie->video_file);
            }
            $movie->video_file = $data['video_file']->store('videos', 'public');
        }

        $movie->save();
        return $movie;
    }

    public function deleteMovie($id)
    {
        $movie = Movie::findOrFail($id);
        // Delete the video file if it exists
        if ($movie->video_file) {
            Storage::disk('public')->delete($movie->video_file);
        }
        $movie->delete();
    }
}