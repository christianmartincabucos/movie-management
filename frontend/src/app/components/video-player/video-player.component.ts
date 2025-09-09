import { Component, Input, OnInit, AfterViewInit, ElementRef, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TimeFormatPipe } from '../../pipes/time-format.pipe';

@Component({
  selector: 'app-video-player',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './video-player.component.html',
  styleUrls: ['./video-player.component.scss']
})
export class VideoPlayerComponent implements OnInit, AfterViewInit {
  @ViewChild('videoElement') videoElementRef!: ElementRef<HTMLVideoElement>;
  @Input() controls: boolean = false;
  @Input() videoUrl: string = '';
  @Input() posterUrl: string = '';
  @Input() autoplay: boolean = false;
  @Input() muted: boolean = false;
  @Input() loop: boolean = false;
  @Input() height: number | null = null;
  @Input() width: number | null = null;
  @Input() showControls: boolean = true;

  loading: boolean = true;
  error: string = '';
  isPlaying: boolean = false;
  isMuted: boolean = false;
  volume: number = 1;
  currentTime: number = 0;
  duration: number = 0;
  progressPercentage: number = 0;
  
  ngOnInit(): void {
    console.log('VideoPlayerComponent initialized with URL:', this.videoUrl);
  }
  
  ngAfterViewInit(): void {
    const videoElement = this.videoElementRef?.nativeElement;
    if (!videoElement) return;
    
    // Make sure video element has proper cross-origin settings
    videoElement.crossOrigin = 'anonymous';
    
    // Listen for video events
    videoElement.addEventListener('timeupdate', () => {
      this.currentTime = videoElement.currentTime;
      this.progressPercentage = (this.currentTime / this.duration) * 100;
    });
    
    videoElement.addEventListener('loadedmetadata', () => {
      console.log('Video metadata loaded, duration:', videoElement.duration);
      this.duration = videoElement.duration;
    });
    
    videoElement.addEventListener('ended', () => {
      this.isPlaying = false;
    });
    
    // Set initial volume
    videoElement.volume = this.volume;
    this.isMuted = videoElement.muted;
  }
  
  onLoadStart(): void {
    console.log('Video load started');
    this.loading = true;
  }
  
  onCanPlay(): void {
    console.log('Video can play now');
    this.loading = false;
  }
  
  onError(event: any): void {
    console.error('Video error event:', event);
    console.error('Video error:', this.videoElementRef?.nativeElement.error);
    this.loading = false;
    this.error = 'Error loading video. Please try again later.';
  }
  
  togglePlayPause(): void {
    const videoElement = this.videoElementRef?.nativeElement;
    if (!videoElement) return;
    
    if (videoElement.paused) {
      videoElement.play()
        .then(() => {
          this.isPlaying = true;
        })
        .catch(error => {
          console.error('Error playing video:', error);
          this.error = 'Could not play video: ' + (error.message || 'Unknown error');
        });
    } else {
      videoElement.pause();
      this.isPlaying = false;
    }
  }
  
  seekVideo(event: MouseEvent): void {
    const videoElement = this.videoElementRef?.nativeElement;
    if (!videoElement) return;
    
    const progressBar = event.currentTarget as HTMLElement;
    const rect = progressBar.getBoundingClientRect();
    const pos = (event.clientX - rect.left) / rect.width;
    videoElement.currentTime = pos * videoElement.duration;
  }
  
  toggleMute(): void {
    const videoElement = this.videoElementRef?.nativeElement;
    if (!videoElement) return;
    
    videoElement.muted = !videoElement.muted;
    this.isMuted = videoElement.muted;
  }
  
  setVolume(event: Event): void {
    const videoElement = this.videoElementRef?.nativeElement;
    if (!videoElement) return;
    
    const input = event.target as HTMLInputElement;
    this.volume = parseFloat(input.value);
    videoElement.volume = this.volume;
    
    if (this.volume === 0) {
      videoElement.muted = true;
      this.isMuted = true;
    } else if (this.isMuted) {
      videoElement.muted = false;
      this.isMuted = false;
    }
  }
  
  toggleFullscreen(): void {
    const videoContainer = this.videoElementRef?.nativeElement.parentElement;
    if (!videoContainer) return;
    
    if (!document.fullscreenElement) {
      if (videoContainer.requestFullscreen) {
        videoContainer.requestFullscreen();
      }
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      }
    }
  }
}