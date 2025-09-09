import { Injectable } from '@angular/core';
import { HttpClient, HttpEventType } from '@angular/common/http';
import { Observable, Subject } from 'rxjs';
import { environment } from '../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class FileUploadService {
  constructor(private http: HttpClient) {}

  uploadFileInChunks(file: File, chunkSize = 1048576): Observable<any> {
    const subject = new Subject<any>();
    const fileSize = file.size;
    const chunkCount = Math.ceil(fileSize / chunkSize);
    let currentChunk = 0;
    let uploadedUuid: string | null = null;

    const uploadNext = () => {
      const start = currentChunk * chunkSize;
      const end = Math.min(start + chunkSize, fileSize);
      const chunk = file.slice(start, end);
      
      const formData = new FormData();
      formData.append('file', chunk, file.name);
      formData.append('chunk', currentChunk.toString());
      formData.append('chunks', chunkCount.toString());
      
      if (uploadedUuid) {
        formData.append('uuid', uploadedUuid);
      }

      this.http.post<any>(`${environment.apiUrl}`, formData, {
        reportProgress: true,
        observe: 'events'
      }).subscribe({
        next: (event: any) => {
          if (event.type === HttpEventType.UploadProgress) {
            const totalProgress = ((currentChunk * chunkSize + event.loaded) / fileSize) * 100;
            subject.next({ type: 'progress', value: Math.round(totalProgress) });
          } else if (event.type === HttpEventType.Response) {
            if (event.body && event.body.success) {
              if (!uploadedUuid && event.body.uuid) {
                uploadedUuid = event.body.uuid;
              }
              
              currentChunk++;
              if (currentChunk < chunkCount) {
                uploadNext();
              } else {
                subject.next({ 
                  type: 'complete', 
                  success: true,
                  uuid: uploadedUuid
                });
                subject.complete();
              }
            } else {
              subject.next({ 
                type: 'complete', 
                success: false, 
                error: event.body && event.body.error ? event.body.error : 'Unknown error'
              });
              subject.complete();
            }
          }
        },
        error: (err) => {
          subject.next({ type: 'complete', success: false, error: err.message || 'Upload failed' });
          subject.complete();
        }
      });
    };

    uploadNext();
    return subject.asObservable();
  }
}