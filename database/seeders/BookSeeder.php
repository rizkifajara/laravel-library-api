<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Author;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure there are authors to associate books with
        $authors = Author::all();

        if ($authors->isEmpty()) {
            // If no authors exist, create some authors first
            $authors = Author::factory()->count(10)->create();
        }

        // Create 50 books and randomly assign them to authors
        Book::factory()->count(50)->create()->each(function ($book) use ($authors) {
            $book->author_id = $authors->random()->id;
            $book->save();
        });
    }
}
