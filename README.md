# Laravel Library REST API

This project is a Laravel-based REST API for managing authors and books for library. It uses Docker for easy setup and deployment, but can also be run manually.

## Prerequisites

Before you begin, ensure you have the following installed on your system:
- [Docker](https://www.docker.com/get-started) and [Docker Compose](https://docs.docker.com/compose/install/) (for Docker setup)
- PHP 8.2 or higher
- Composer
- PostgreSQL
- Redis (optional, for caching)

## Getting Started with Docker

To get the project up and running with Docker, follow these steps:

1. Clone the repository:
   ```
   git clone https://github.com/your-username/your-repo-name.git
   cd your-repo-name
   ```

2. Copy the example environment file:
   ```
   cp .env.example .env
   ```

3. Build and start the Docker containers:
   ```
   docker-compose up -d --build
   ```

4. Install PHP dependencies:
   ```
   docker-compose exec app composer install
   ```

5. Generate application key:
   ```
   docker-compose exec app php artisan key:generate
   ```

6. Run database migrations and seeders:
   ```
   docker-compose exec app php artisan migrate --seed
   ```

## Manual Setup (Without Docker)

To set up and run the project manually without Docker, follow these steps:

1. Clone the repository:
   ```
   git clone https://github.com/your-username/your-repo-name.git
   cd your-repo-name
   ```

2. Copy the example environment file and update it with your local database credentials:
   ```
   cp .env.example .env
   ```

3. Install PHP dependencies:
   ```
   composer install
   ```

4. Generate application key:
   ```
   php artisan key:generate
   ```

5. Create a new PostgreSQL database for the project.

6. Update the `.env` file with your database credentials:
   ```
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_username
   DB_PASSWORD=your_database_password
   ```

7. Run database migrations and seeders:
   ```
   php artisan migrate --seed
   ```

8. Start the development server:
   ```
   php artisan serve
   ```

The API will be available at `http://localhost:8000`.

## API Endpoints

The API provides the following endpoints:

- `GET /api/authors`: List all authors
- `GET /api/authors/{id}`: Get a specific author
- `POST /api/authors`: Create a new author
- `PUT /api/authors/{id}`: Update an existing author
- `DELETE /api/authors/{id}`: Delete an author
- `GET /api/authors/{id}/books`: Get books by a specific author

- `GET /api/books`: List all books
- `GET /api/books/{id}`: Get a specific book
- `POST /api/books`: Create a new book
- `PUT /api/books/{id}`: Update an existing book
- `DELETE /api/books/{id}`: Delete a book

## Testing

To run the tests, use the following command:

With Docker:
```
docker-compose exec app php artisan test
```

Without Docker:
```
php artisan test
```

## Stopping the Application

With Docker:
To stop the Docker containers, run:
```
docker-compose down
```

Without Docker:
If you're running the application with `php artisan serve`, you can stop it by pressing `Ctrl+C` in the terminal where it's running.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.