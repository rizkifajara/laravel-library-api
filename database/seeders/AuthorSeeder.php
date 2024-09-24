<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuthorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate the tables to reset auto-increment values
        DB::statement('TRUNCATE TABLE books RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE authors RESTART IDENTITY CASCADE');

        // Create 10 authors
        Author::factory()->count(10)->create()->each(function ($author) {
            // For each author, create 5 books
            Book::factory()->count(5)->create(['author_id' => $author->id]);
        });
    }
}
