import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { MovieService } from '../../services/movie.service';
import { Movie } from '../../models/movie.model';
import { environment } from '../../environments/environment';
import { VideoPlayerComponent } from '../video-player/video-player.component';
import { RouterModule } from '@angular/router';
import { DatePipe } from '@angular/common';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-movie-details',
  templateUrl: './movie-details.component.html',
  styleUrls: ['./movie-details.component.scss'],
  imports: [VideoPlayerComponent, RouterModule, DatePipe, CommonModule],
  standalone: true
})
export class MovieDetailsComponent implements OnInit {
  movieId!: number;
  movie: Movie | null = null;
  loading = true;
  error = '';
  thumbnailLoading = false;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private movieService: MovieService
  ) {}

  ngOnInit(): void {
    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      if (id) {
        this.movieId = +id;
        this.loadMovie();
      }
    });
  }

  loadMovie(): void {
    this.loading = true;
    this.error = '';
    
    this.movieService.getMovie(this.movieId).subscribe({
      next: (movie) => {
        this.movie = movie;
        this.loading = false;
        
        // Generate thumbnail if it doesn't exist
        if (!this.movie.thumbnail) {
          this.generateMovieThumbnail();
        }
      },
      error: (err) => {
        console.error('Error loading movie:', err);
        this.error = 'Failed to load movie details. Please try again.';
        this.loading = false;
      }
    });
  }

  // Use the generateThumbnail method here
  generateMovieThumbnail(): void {
    if (!this.movieId) return;
    
    this.thumbnailLoading = true;
    
    this.movieService.generateThumbnail(this.movieId).subscribe({
      next: (response) => {
        if (response && response.thumbnail) {
          this.movie!.thumbnail = response.thumbnail;
        }
        this.thumbnailLoading = false;
      },
      error: (err) => {
        console.error('Error generating thumbnail:', err);
        this.thumbnailLoading = false;
      }
    });
  }

  // Use the getThumbnailUrl method here
  getThumbnailUrl(): string {
    if (!this.movie || !this.movie.thumbnail) return '';
    return this.movieService.getThumbnailUrl(this.movie.thumbnail);
  }
  
  getVideoUrl(): string {
    if (!this.movie || !this.movie.video_file) return '';
    
    // Extract just the filename from the path
    const filename = typeof this.movie.video_file === 'string' 
      ? (this.movie.video_file as string).split('/').pop()
      : this.movie.video_file instanceof File 
        ? this.movie.video_file.name
        : String(this.movie.video_file);
    
    // Use the streaming API endpoint
    return `/storage/videos/${filename}`;
  }
  confirmDelete() {
    if (confirm('Are you sure you want to delete this movie?')) {
      // Implement your delete logic here
      // For example:
      // this.movieService.deleteMovie(this.movieId).subscribe({
      //   next: () => this.router.navigate(['/movies']),
      //   error: (err) => this.error = 'Failed to delete movie: ' + err.message
      // });
    }
  }
}