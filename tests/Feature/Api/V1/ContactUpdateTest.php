<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_contact(): void
    {
        $category = Category::create([
            'content' => 'テストカテゴリー',
        ]);

        $tag1 = Tag::create([
            'name' => '質問',
        ]);

        $tag2 = Tag::create([
            'name' => '要望',
        ]);

        $contact = Contact::create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'old@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => null,
            'detail' => '更新前のお問い合わせ',
        ]);

        $contact->tags()->attach($tag1->id);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", [
            'first_name' => '次郎',
            'last_name' => '佐藤',
            'gender' => 2,
            'email' => 'new@example.com',
            'tel' => '08012345678',
            'address' => '大阪府',
            'building' => 'テストビル',
            'category_id' => $category->id,
            'detail' => '更新後のお問い合わせ',
            'tag_ids' => [$tag2->id],
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'first_name' => '次郎',
            'last_name' => '佐藤',
            'email' => 'new@example.com',
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '次郎',
            'last_name' => '佐藤',
            'email' => 'new@example.com',
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag2->id,
        ]);

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag1->id,
        ]);
    }

    public function test_returns_404_when_contact_does_not_exist(): void
    {
        $response = $this->putJson('/api/v1/contacts/99999', [
            'first_name' => '次郎',
            'last_name' => '佐藤',
            'gender' => 2,
            'email' => 'new@example.com',
            'tel' => '08012345678',
            'address' => '大阪府',
            'building' => 'テストビル',
            'category_id' => 1,
            'detail' => '更新後のお問い合わせ',
            'tag_ids' => [],
        ]);

        $response->assertStatus(404);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $category = Category::create([
            'content' => 'テストカテゴリー',
        ]);

        $contact = Contact::create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => null,
            'detail' => 'お問い合わせ内容',
        ]);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", []);

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
}
