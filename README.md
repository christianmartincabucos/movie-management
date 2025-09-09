# Movie Management Platform

## Tech Stack
- **Backend**: PHP, Laravel
- **Frontend**: Angular
- **Database**: MySQL (or any other preferred database)
- **File Storage**: Local storage (for video files)
- **Testing**: PHPUnit for backend, Jasmine/Karma for frontend
- **Containerization**: Docker (optional)

## Prerequisites
- PHP >= 7.4
- Composer
- Node.js >= 14
- Angular CLI
- MySQL (or any other preferred database)
- Docker (optional)

## Setup Instructions

### Backend Setup
1. Navigate to the `backend` directory:
   ```
   cd backend
   ```
2. Install PHP dependencies using Composer:
   ```
   composer install
   ```
3. Copy the example environment file and set up your environment variables:
   ```
   cp .env.example .env
   ```
   Update the `.env` file with your database credentials.

4. Run the migrations to create the database tables:
   ```
   php artisan migrate
   ```

5. (Optional) Seed the database with initial data:
   ```
   php artisan db:seed
   ```

6. Start the Laravel development server:
   ```
   php artisan serve
   ```

### Frontend Setup
1. Navigate to the `frontend` directory:
   ```
   cd frontend
   ```
2. Install Node.js dependencies using npm:
   ```
   npm install
   ```
3. Start the Angular development server:
   ```
   ng serve
   ```

### Docker Setup (Optional)
1. Ensure Docker is installed and running.
2. Navigate to the `backend` directory and build the Docker image:
   ```
   docker-compose up --build
   ```
3. Access the application at `http://localhost:8000` (or the port specified in your `docker-compose.yml`).

## Known Issues or Limitations
- Video processing in the background is not implemented in this version.
- User authentication is not included in this version.
- The UI may require further enhancements for better user experience.

## Demo Instructions
1. Access the frontend application at `http://localhost:4200`.
2. Use the application to create, view, edit, and delete movies.
3. Test file upload by selecting a video file during movie creation or editing.
4. Play the uploaded video using the video player component.

For a demo video, please refer to [Demo Video Link] (insert actual link here).