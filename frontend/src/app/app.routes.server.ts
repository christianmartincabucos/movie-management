import { RenderMode, ServerRoute } from '@angular/ssr';

export const serverRoutes: ServerRoute[] = [
  {
    path: 'movies/:id',
    renderMode: RenderMode.Client
  },
  {
    path: 'edit/:id',
    renderMode: RenderMode.Client
  },
  {
    path: 'play/:id',
    renderMode: RenderMode.Client
  },
  {
    path: '**',
    renderMode: RenderMode.Prerender
  }
];