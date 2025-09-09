import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'timeFormat',
  standalone: true
})
export class TimeFormatPipe implements PipeTransform {
  transform(value: number): string {
    if (isNaN(value)) {
      return '00:00';
    }
    
    const minutes: number = Math.floor(value / 60);
    const seconds: number = Math.floor(value % 60);
    
    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
  }
}