<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Movie::create([
            'title' => 'Inception',
            'description' => 'A mind-bending thriller by Christopher Nolan.',
            'date_added' => now(),
            'video_file' => 'inception.mp4',
        ]);

        Movie::create([
            'title' => 'The Matrix',
            'description' => 'A sci-fi classic that questions reality.',
            'date_added' => now(),
            'video_file' => 'the_matrix.mp4',
        ]);

        Movie::create([
            'title' => 'Interstellar',
            'description' => 'A journey through space and time.',
            'date_added' => now(),
            'video_file' => 'interstellar.mp4',
        ]);
    }
}
