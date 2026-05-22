<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCreationTest extends TestCase
{
    use RefreshDatabase;

    private function validBookData(): array
    {
        return [
            'title'   => 'Clean Code',
            'author'  => 'Robert C. Martin',
            'summary' => 'A handbook of agile software craftsmanship.',
            'isbn'    => '9780132350884',
        ];
    }

    public function test_authenticated_user_book_creation(): void
    {
        $user = User::factory()->create();
        $data = $this->validBookData();

        $response = $this->actingAs($user)->postJson('/api/books', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('books', [
            'title' => $data['title'],
            'isbn'  => $data['isbn'],
        ]);
    }

    public function test_book_creation_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $data = $this->validBookData();
        $data['title'] = 'ab';

        $response = $this->actingAs($user)->postJson('/api/books', $data);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('books', ['isbn' => $data['isbn']]);
    }

    public function test_guest_book_creation(): void
    {
        $data = $this->validBookData();

        $response = $this->postJson('/api/books', $data);

        $response->assertStatus(401);
        $this->assertDatabaseMissing('books', ['isbn' => $data['isbn']]);
    }
}
