import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MovieService } from '../../services/movie.service';
import { Movie } from '../../models/movie.model';
import { VideoPlayerComponent } from '../video-player/video-player.component';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-movie-edit',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, VideoPlayerComponent],
  templateUrl: './movie-edit.component.html',
  styleUrls: ['./movie-edit.component.scss']
})
export class MovieEditComponent implements OnInit {
  movieForm!: FormGroup;
  movie: Movie | null = null;
  loading: boolean = true;
  error: string = '';
  movieId!: number;
  selectedFile: File | null = null;
  submitting: boolean = false;

  constructor(
    private fb: FormBuilder,
    private movieService: MovieService,
    private route: ActivatedRoute,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.initForm();
    
    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      if (id) {
        this.movieId = +id;
        this.loadMovie();
      } else {
        this.error = 'Movie ID not provided';
        this.loading = false;
      }
    });
  }

  initForm(): void {
    this.movieForm = this.fb.group({
      title: ['', [Validators.required]],
      description: ['', [Validators.required]],
      date_added: ['', [Validators.required]]
    });
  }

  loadMovie(): void {
    this.loading = true;
    this.error = '';
    
    this.movieService.getMovie(this.movieId).subscribe({
      next: (movie) => {
        this.movie = movie;
        this.populateForm(movie);
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading movie:', err);
        this.error = 'Failed to load movie data. Please try again.';
        this.loading = false;
      }
    });
  }

  populateForm(movie: Movie): void {
    // Format the date as YYYY-MM-DD for date input
    const formattedDate = movie.date_added ? 
      new Date(movie.date_added).toISOString().split('T')[0] : '';
      
    this.movieForm.patchValue({
      title: movie.title,
      description: movie.description,
      date_added: formattedDate
    });
  }

  onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length) {
      this.selectedFile = input.files[0];
    } else {
      this.selectedFile = null;
    }
  }

  getVideoFileName(): string {
    if (!this.movie?.video_file) return '';
    // Extract just the file name from the path
    const pathParts = (this.movie.video_file as unknown as string).split('/');
    return pathParts[pathParts.length - 1];
  }

  getVideoUrl(): string {
    if (!this.movie?.video_file) return '';
    return `${environment.apiUrl}/storage/${this.movie.video_file}`;
  }

  isFieldInvalid(fieldName: string): boolean {
    const field = this.movieForm.get(fieldName);
    return field ? field.invalid && (field.dirty || field.touched) : false;
  }

  onSubmit(): void {
    if (this.movieForm.invalid) {
      // Mark all fields as touched to display validation errors
      Object.keys(this.movieForm.controls).forEach(key => {
        const control = this.movieForm.get(key);
        control?.markAsTouched();
      });
      return;
    }

    this.submitting = true;
    
    const formData = new FormData();
    formData.append('title', this.movieForm.get('title')?.value);
    formData.append('description', this.movieForm.get('description')?.value);
    formData.append('date_added', this.movieForm.get('date_added')?.value);
    
    if (this.selectedFile) {
      formData.append('video_file', this.selectedFile);
    }
    
    // Add method to indicate we want to update the record even if no file is uploaded
    if (!this.selectedFile) {
      formData.append('_method', 'PUT');
    }
    
    this.movieService.updateMovie(this.movieId, formData).subscribe({
      next: () => {
        this.submitting = false;
        this.router.navigate(['/movies', this.movieId]);
      },
      error: (err) => {
        console.error('Error updating movie:', err);
        this.error = 'Failed to update movie. Please try again.';
        this.submitting = false;
      }
    });
  }
}