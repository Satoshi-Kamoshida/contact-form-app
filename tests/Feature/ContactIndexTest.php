<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_index_is_displayed(): void
    {
        Category::create([
            'content' => 'テストカテゴリ',
        ]);

        Tag::create([
            'name' => 'テストタグ',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('テストカテゴリ');
        $response->assertSee('テストタグ');
    }
}
