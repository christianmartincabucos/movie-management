import { Injectable } from '@angular/core';
import { HttpClient, HttpEvent, HttpRequest } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Movie } from '../models/movie.model';
import { environment } from '../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class MovieService {
  private apiUrl = `${environment.apiUrl}/movies`;

  constructor(private http: HttpClient) { }

  getMovies(): Observable<Movie[]> {
    return this.http.get<Movie[]>(this.apiUrl);
  }

  getMovie(id: number): Observable<Movie> {
    return this.http.get<Movie>(`${this.apiUrl}/${id}`);
  }

  createMovie(movieData: FormData): Observable<Movie> {
    return this.http.post<Movie>(this.apiUrl, movieData);
  }

  updateMovie(id: number, movieData: FormData): Observable<Movie> {
    return this.http.post<Movie>(`${this.apiUrl}/${id}`, movieData);
  }

  deleteMovie(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }

  // New methods with progress tracking
  createMovieWithProgress(movieData: FormData): Observable<HttpEvent<any>> {
    const req = new HttpRequest('POST', this.apiUrl, movieData, {
      reportProgress: true,
      responseType: 'json'
    });
    return this.http.request(req);
  }

  updateMovieWithProgress(id: number, movieData: FormData): Observable<HttpEvent<any>> {
    const req = new HttpRequest('POST', `${this.apiUrl}/${id}`, movieData, {
      reportProgress: true,
      responseType: 'json'
    });
    return this.http.request(req);
  }
  generateThumbnail(id: number): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/${id}/thumbnail`);
  }
  
  getThumbnailUrl(path: string): string {
    if (!path) return '';
    const filename = path.split('/').pop();
    return `${this.apiUrl}/thumbnails/${filename}`;
  }
}