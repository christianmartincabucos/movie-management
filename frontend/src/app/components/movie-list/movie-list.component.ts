import { Component, OnInit } from '@angular/core';
import { MovieService } from '../../services/movie.service';
import { Movie } from '../../models/movie.model';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-movie-list',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './movie-list.component.html',
  styleUrls: ['./movie-list.component.scss']
})
export class MovieListComponent implements OnInit {
  movies: Movie[] = [];
  loading = true;
  error = '';

  constructor(private movieService: MovieService) {}

  ngOnInit(): void {
    this.loadMovies();
  }

  loadMovies(): void {
    this.loading = true;
    this.movieService.getMovies().subscribe({
      next: (movies) => {
        this.movies = movies;
        this.loading = false;
      },
      error: (err) => {
        this.error = 'Failed to load movies';
        this.loading = false;
        console.error(err);
      }
    });
  }

  deleteMovie(id: number): void {
    if (confirm('Are you sure you want to delete this movie?')) {
      this.movieService.deleteMovie(id).subscribe({
        next: () => {
          this.movies = this.movies.filter(movie => movie.id !== id);
        },
        error: (err) => {
          this.error = 'Failed to delete movie';
          console.error(err);
        }
      });
    }
  }
  getThumbnailUrl(movie: Movie): string {
    if (!movie.thumbnail) return ''; // You could return a placeholder image here
    return this.movieService.getThumbnailUrl(movie.thumbnail);
  }
}