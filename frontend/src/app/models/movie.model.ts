export interface Movie {
  id?: number;
  title: string;
  description: string;
  date_added: Date;
  video_file?: File;
  thumbnail?: string;
  hls_path?: string;
  is_processed?: boolean;
  processing_error?: number;
}