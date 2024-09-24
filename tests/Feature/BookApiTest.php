<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Book;
use App\Models\Author;
use Illuminate\Support\Facades\Cache;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // Clear the cache before each test
    }

    public function test_can_get_all_books()
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/books');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_book()
    {
        $author = Author::factory()->create();
        $bookData = [
            'title' => 'Test Book',
            'description' => 'A test book description',
            'publish_date' => '2023-01-01',
            'author_id' => $author->id
        ];

        $response = $this->postJson('/api/books', $bookData);

        $response->assertStatus(201)
            ->assertJsonFragment($bookData);

        $this->assertDatabaseHas('books', $bookData);
    }

    public function test_can_update_book()
    {
        $book = Book::factory()->create();
        $updateData = ['title' => 'Updated Title'];

        $response = $this->putJson("/api/books/{$book->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('books', $updateData);
    }

    public function test_can_delete_book()
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_can_get_book_with_author()
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => [
                        'id' => $book->author->id,
                        'name' => $book->author->name
                    ]
                ]
            ]);
    }

    public function test_default_parameters_for_index()
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/books');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
                'links',
                'status'
            ]);
    }

    public function test_validation_errors()
    {
        $invalidData = [
            'title' => '',
            'description' => '',
            'publish_date' => 'invalid-date',
            'author_id' => null
        ];

        $response = $this->postJson('/api/books', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    'title',
                    'description',
                    'publish_date',
                    'author_id'
                ],
                'status'
            ]);
    }

    public function test_pagination()
    {
        Book::factory()->count(30)->create();

        $response = $this->getJson('/api/books?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
                'links',
                'status'
            ])
            ->assertJsonCount(10, 'data');
    }

    public function test_fields_parameter()
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/books?fields=id,title");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title'
                    ]
                ]
            ]);

        $responseData = $response->json('data')[0];
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('title', $responseData);
        $this->assertArrayNotHasKey('description', $responseData);
        $this->assertArrayNotHasKey('publish_date', $responseData);
        $this->assertArrayNotHasKey('author_id', $responseData);
    }
}
