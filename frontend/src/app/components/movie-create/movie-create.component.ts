import { Component } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MovieService } from '../../services/movie.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-movie-create',
  templateUrl: './movie-create.component.html',
  styleUrls: ['./movie-create.component.scss']
})
export class MovieCreateComponent {
  movieForm: FormGroup;
  loading = false;
  successMessage: string = '';
  errorMessage: string = '';

  constructor(
    private formBuilder: FormBuilder,
    private movieService: MovieService,
    private router: Router
  ) {
    this.movieForm = this.formBuilder.group({
      title: ['', Validators.required],
      description: ['', Validators.required],
      date_added: ['', Validators.required],
      video_file: [null, Validators.required]
    });
  }

  onFileChange(event: any) {
    const file = event.target.files[0];
    if (file) {
      this.movieForm.patchValue({
        video_file: file
      });
    }
  }

  onSubmit() {
    if (this.movieForm.invalid) {
      return;
    }

    this.loading = true;
    const formData = new FormData();
    formData.append('title', this.movieForm.get('title')?.value);
    formData.append('description', this.movieForm.get('description')?.value);
    formData.append('date_added', this.movieForm.get('date_added')?.value);
    formData.append('video_file', this.movieForm.get('video_file')?.value);

    this.movieService.createMovie(formData).subscribe(
      response => {
        this.successMessage = 'Movie created successfully!';
        this.loading = false;
        this.router.navigate(['/movies']);
      },
      error => {
        this.errorMessage = 'Error creating movie. Please try again.';
        this.loading = false;
      }
    );
  }
}