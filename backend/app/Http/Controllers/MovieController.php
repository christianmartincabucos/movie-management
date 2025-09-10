<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Services\MovieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MovieController extends Controller
{
    protected $movieService;

    public function __construct(MovieService $movieService)
    {
        $this->movieService = $movieService;
    }

    public function index(): JsonResponse
    {
        $movies = $this->movieService->getAllMovies();
        return response()->json($movies);
    }

    public function show($id): JsonResponse
    {
        $movie = $this->movieService->getMovie($id);
        return response()->json($movie);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date_added' => 'required|date',
            'uuid' => 'required_without:video_file|string', // UUID for chunked upload
            'video_file' => 'required_without:uuid|file|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $videoPath = $this->movieService->handleVideoUpload($request);

        if (!$videoPath) {
            return response()->json(['error' => 'No valid video file provided'], 400);
        }

        $movie = $this->movieService->createMovie($request->all(), $videoPath);

        return response()->json([
            'movie' => $movie,
            'message' => 'Movie created successfully. Video processing has started.'
        ], 201);
    }

    public function update(Request $request, Movie $movie): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'date_added' => 'sometimes|required|date',
            'uuid' => 'sometimes|required_without:video_file|string',
            'video_file' => 'sometimes|required_without:uuid|file|mimetypes:video/mp4,video/mpeg,video/quicktime',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $videoPath = null;
        if ($request->hasFile('video_file') || $request->has('uuid')) {
            $videoPath = $this->movieService->handleVideoUpload($request);
            
            if (!$videoPath) {
                return response()->json(['error' => 'File upload not found or incomplete'], 400);
            }
        }

        $updatedMovie = $this->movieService->updateMovie($movie, $request->all(), $videoPath);
        
        return response()->json([
            'movie' => $updatedMovie,
            'message' => $videoPath ? 
                'Movie updated successfully. Video processing has started.' : 
                'Movie updated successfully.'
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->movieService->deleteMovie($id);
        return response()->json(null, 204);
    }
}