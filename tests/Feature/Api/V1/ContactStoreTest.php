<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_contact(): void
    {
        $category = Category::create([
            'content' => '商品の交換について',
        ]);

        $tag = Tag::create([
            'name' => '質問',
        ]);

        $response = $this->postJson('/api/v1/contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => null,
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です',
            'tag_ids' => [$tag->id],
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'yamada@example.com',
        ]);

        $this->assertDatabaseHas('contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'yamada@example.com',
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => Contact::first()->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/contacts', []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);
    }

    public function test_validation_fails_with_invalid_category_and_tag_ids(): void
    {
        $response = $this->postJson('/api/v1/contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'category_id' => 999,
            'detail' => 'お問い合わせ内容です',
            'tag_ids' => [999],
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'category_id',
            'tag_ids.0',
        ]);
    }
}
