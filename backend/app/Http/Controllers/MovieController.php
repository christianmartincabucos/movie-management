<?php

namespace App\Http\Controllers;

use App\Http\Requests\MovieRequest;
use App\Jobs\ProcessVideoFile;
use App\Models\Movie;
use App\Models\FileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MovieController extends Controller
{
    public function index(): JsonResponse
    {
        $movies = Movie::all();
        return response()->json($movies);
    }

    public function show($id): JsonResponse
    {
        $movie = Movie::findOrFail($id);
        return response()->json($movie);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date_added' => 'required|date',
            'uuid' => 'required_without:video_file|string', // UUID for chunked upload
            'video_file' => 'required_without:uuid|file|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo', // No size limit for regular uploads
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $videoPath = null;

        // Handle regular file upload if present
        if ($request->hasFile('video_file')) {
            $videoPath = $request->file('video_file')->store('videos', 'public');
        } 
        // Handle chunked upload reference
        else if ($request->has('uuid')) {
            $fileUpload = FileUpload::where('uuid', $request->uuid)
                                   ->where('status', 'completed')
                                   ->first();
            
            if (!$fileUpload) {
                return response()->json(['error' => 'File upload not found or incomplete'], 400);
            }
            
            $videoPath = $fileUpload->final_path;
        }

        if (!$videoPath) {
            return response()->json(['error' => 'No valid video file provided'], 400);
        }

        $movie = Movie::create([
            'title' => $request->title,
            'description' => $request->description,
            'date_added' => $request->date_added,
            'video_file' => $videoPath,
            'is_processed' => false,
        ]);

        // Dispatch job to process the video
        ProcessVideoFile::dispatch($movie);

        return response()->json([
            'movie' => $movie,
            'message' => 'Movie created successfully. Video processing has started.'
        ], 201);
    }

    public function update(Request $request, Movie $movie)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'date_added' => 'sometimes|required|date',
            'uuid' => 'sometimes|required_without:video_file|string', // UUID for chunked upload
            'video_file' => 'sometimes|required_without:uuid|file|mimetypes:video/mp4,video/mpeg,video/quicktime', // No size limit
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateVideo = false;
        $videoPath = null;

        // Handle regular file upload
        if ($request->hasFile('video_file')) {
            $videoPath = $request->file('video_file')->store('videos', 'public');
            $updateVideo = true;
        } 
        // Handle chunked upload reference
        else if ($request->has('uuid')) {
            $fileUpload = FileUpload::where('uuid', $request->uuid)
                                   ->where('status', 'completed')
                                   ->first();
            
            if (!$fileUpload) {
                return response()->json(['error' => 'File upload not found or incomplete'], 400);
            }
            
            $videoPath = $fileUpload->final_path;
            $updateVideo = true;
        }

        if ($updateVideo) {
            // Delete old video files
            Storage::disk('public')->delete($movie->video_file);
            if ($movie->thumbnail) {
                Storage::disk('public')->delete($movie->thumbnail);
            }
            if ($movie->hls_path) {
                // Delete HLS directory
                $hlsDir = dirname($movie->hls_path);
                Storage::disk('public')->deleteDirectory($hlsDir);
            }
            
            $movie->video_file = $videoPath;
            $movie->is_processed = false;
            $movie->thumbnail = null;
            $movie->hls_path = null;
        }

        $movie->title = $request->title ?? $movie->title;
        $movie->description = $request->description ?? $movie->description;
        $movie->date_added = $request->date_added ?? $movie->date_added;
        $movie->save();

        // If video was updated, process it again
        if ($updateVideo) {
            ProcessVideoFile::dispatch($movie);
        }

        return response()->json([
            'movie' => $movie,
            'message' => $updateVideo ? 
                'Movie updated successfully. Video processing has started.' : 
                'Movie updated successfully.'
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $movie = Movie::findOrFail($id);
        if ($movie->video_file) {
            Storage::disk('public')->delete($movie->video_file);
        }
        $movie->delete();
        return response()->json(null, 204);
    }
}