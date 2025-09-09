import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MovieService } from '../../services/movie.service';
import { FileUploadService } from '../../services/file-upload.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-movie-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  providers: [FileUploadService],
  templateUrl: './movie-form.component.html',
  styleUrls: ['./movie-form.component.scss']
})
export class MovieFormComponent implements OnInit {
  movieForm!: FormGroup;
  isEditMode = false;
  movieId?: number;
  loading = false;
  error = '';
  selectedFile: File | null = null;
  uploadProgress = 0;
  isUploading = false;
  maxFileSize = 0; // No limit
  fileSizeError = '';
  uploadedFileUuid: string | null = null;
  
  constructor(
    private fb: FormBuilder,
    private movieService: MovieService,
    private fileUploadService: FileUploadService,
    private route: ActivatedRoute,
    private router: Router
  ) {}
  
  ngOnInit(): void {
    // Initialize the form
    this.movieForm = this.fb.group({
      title: ['', Validators.required],
      description: ['', Validators.required],
      date_added: [new Date().toISOString().split('T')[0], Validators.required]
    });

    // Check if we're in edit mode
    this.route.params.subscribe(params => {
      if (params['id']) {
        this.isEditMode = true;
        this.movieId = +params['id'];
        
        // Fetch the movie data
        this.loading = true;
        this.movieService.getMovie(this.movieId).subscribe({
          next: (movie) => {
            // Populate the form with existing data
            this.movieForm.patchValue({
              title: movie.title,
              description: movie.description,
              date_added: movie.date_added ? new Date(movie.date_added).toISOString().split('T')[0] : ''
            });
            this.loading = false;
          },
          error: (err) => {
            this.error = 'Failed to load movie data';
            this.loading = false;
            console.error(err);
          }
        });
      }
    });
  }

  // Add this method to handle file selection
  onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length) {
      this.selectedFile = input.files[0];
      
      // Check file size if max size is set
      if (this.maxFileSize > 0 && this.selectedFile.size > this.maxFileSize) {
        this.fileSizeError = `File size exceeds the maximum allowed size of ${this.formatFileSize(this.maxFileSize)}`;
        this.selectedFile = null;
        return;
      }
      
      this.fileSizeError = '';
      this.uploadedFileUuid = null;
    }
  }
  
  // Add this method to format file size
  formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
  
  // The rest of your component methods remain the same
  
  onSubmit(): void {
    if (this.movieForm.invalid) {
      // Mark all fields as touched to show validation errors
      Object.keys(this.movieForm.controls).forEach(key => {
        const control = this.movieForm.get(key);
        control?.markAsTouched();
      });
      return;
    }
    
    if (!this.selectedFile && !this.uploadedFileUuid && !this.isEditMode) {
      this.error = 'Please select a video file';
      return;
    }
    
    const formData = new FormData();
    formData.append('title', this.movieForm.get('title')?.value);
    formData.append('description', this.movieForm.get('description')?.value);
    formData.append('date_added', this.movieForm.get('date_added')?.value);
    
    if (this.selectedFile && !this.uploadedFileUuid) {
      // If file is small enough, upload directly
      if (this.selectedFile.size < 10 * 1024 * 1024) {  // Less than 10MB
        formData.append('video_file', this.selectedFile);
        this.submitFormWithData(formData);
      } else {
        // For larger files, use chunked upload
        this.uploadLargeFile();
      }
    } else if (this.uploadedFileUuid) {
      // If we already uploaded the file in chunks
      formData.append('uuid', this.uploadedFileUuid);
      this.submitFormWithData(formData);
    } else {
      // No file change in edit mode
      this.submitFormWithData(formData);
    }
  }
  
  // Add this method if it doesn't exist
  uploadLargeFile(): void {
    if (!this.selectedFile) return;
    
    this.isUploading = true;
    this.uploadProgress = 0;
    this.error = '';
    
    // You'll need to implement the FileUploadService method
    this.fileUploadService.uploadFileInChunks(this.selectedFile).subscribe({
      next: (event: any) => {
        if (event.type === 'progress') {
          this.uploadProgress = event.value;
        } else if (event.type === 'complete' && event.success) {
          this.uploadedFileUuid = event.uuid;
          
          // Submit the form with the UUID
          const formData = new FormData();
          formData.append('title', this.movieForm.get('title')?.value);
          formData.append('description', this.movieForm.get('description')?.value);
          formData.append('date_added', this.movieForm.get('date_added')?.value);
          if (this.uploadedFileUuid) {
            formData.append('uuid', this.uploadedFileUuid);
          }
          
          this.submitFormWithData(formData);
        } else if (event.type === 'complete' && !event.success) {
          this.isUploading = false;
          this.error = `Upload failed: ${event.error}`;
        }
      },
      error: (err) => {
        this.isUploading = false;
        this.error = 'An error occurred during file upload';
        console.error(err);
      }
    });
  }
  
  // Add this method if it doesn't exist
  private submitFormWithData(formData: FormData): void {
    this.loading = true;
    
    if (this.isEditMode && this.movieId) {
      this.movieService.updateMovie(this.movieId, formData).subscribe({
        next: () => {
          this.loading = false;
          this.isUploading = false;
          this.router.navigate(['/movies', this.movieId]);
        },
        error: (err) => {
          this.error = 'Failed to update movie';
          this.loading = false;
          this.isUploading = false;
          console.error(err);
        }
      });
    } else {
      this.movieService.createMovie(formData).subscribe({
        next: (response) => {
          this.loading = false;
          this.isUploading = false;
          this.router.navigate(['/movies']);
        },
        error: (err) => {
          this.error = 'Failed to create movie';
          this.loading = false;
          this.isUploading = false;
          console.error(err);
        }
      });
    }
  }
}