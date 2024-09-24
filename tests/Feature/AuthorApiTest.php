<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Author;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthorApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_can_get_all_authors()
    {
        Author::factory()->count(3)->create();

        $response = $this->getJson('/api/authors');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_author()
    {
        $authorData = [
            'name' => 'Leila Chudori',
            'bio' => 'An author who needs no introduction...',
            'birth_date' => '1962-12-12'
        ];

        $response = $this->postJson('/api/authors', $authorData);

        $response->assertStatus(201)
            ->assertJsonFragment($authorData);

        $this->assertDatabaseHas('authors', $authorData);
    }

    public function test_can_update_author()
    {
        $author = Author::factory()->create();
        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson("/api/authors/{$author->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('authors', $updateData);
    }

    public function test_can_delete_author()
    {
        $author = Author::factory()->create();

        $response = $this->deleteJson("/api/authors/{$author->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('authors', ['id' => $author->id]);
    }

    public function test_can_get_author_books()
    {
        // Create an author
        $author = Author::factory()->create();

        // Create 3 books for this author
        Book::factory()->count(3)->create(['author_id' => $author->id]);

        // Verify that only 3 books exist in the database
        $this->assertEquals(3, Book::count());

        $response = $this->getJson("/api/authors/{$author->id}/books");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data'); // Update the path to match the nested structure

        // Additional check: verify the returned book IDs
        $bookIds = $response->json('data.data.*.id'); // Update the path to match the nested structure
        $this->assertCount(3, $bookIds);
        $this->assertEquals(Book::pluck('id')->toArray(), $bookIds);
    }

    public function test_default_parameters_for_index()
    {
        Author::factory()->count(3)->create();

        $response = $this->getJson('/api/authors');

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
            'name' => '',
            'bio' => '',
            'birth_date' => 'invalid-date'
        ];

        $response = $this->postJson('/api/authors', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    'name',
                    'bio',
                    'birth_date'
                ],
                'status'
            ]);
    }

    public function test_pagination()
    {
        Author::factory()->count(30)->create();

        $response = $this->getJson('/api/authors?per_page=10');

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
        $author = Author::factory()->create();

        $response = $this->getJson("/api/authors?fields=id,name");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name'
                    ]
                ]
            ]);

        $responseData = $response->json('data')[0];
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);
        $this->assertArrayNotHasKey('bio', $responseData);
        $this->assertArrayNotHasKey('birth_date', $responseData);
    }
}
