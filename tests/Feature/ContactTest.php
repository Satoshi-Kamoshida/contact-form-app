<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_confirm_is_displayed(): void
    {
        $category = Category::create([
            'content' => '商品について',
        ]);

        $tag = Tag::create([
            'name' => '質問',
        ]);

        $response = $this->post('/contacts/confirm', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都新宿区',
            'building' => 'テスト',
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
            'detail' => 'テストです。',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('contact.confirm');
        $response->assertSee('山田');
        $response->assertSee('太郎');
        $response->assertSee('test@example.com');
        $response->assertSee('商品について');
        $response->assertSee('質問');
    }

    public function test_thanks_is_displayed(): void
    {
        $response = $this->get('/thanks');

        $response->assertStatus(200);
        $response->assertViewIs('contact.thanks');
    }

    public function test_contact_confirm_validation_error(): void
    {
        $response = $this->post('/contacts/confirm', [
            'first_name' => '',
            'last_name' => '',
            'gender' => '',
            'email' => 'invalid-email',
            'tel' => '123',
            'address' => '',
            'category_id' => '',
            'detail' => '',
        ]);

        $response->assertSessionHasErrors([
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

    public function test_contact_is_stored_and_redirected_to_thanks(): void
    {
        $category = Category::create([
            'content' => '商品について',
        ]);

        $tag = Tag::create([
            'name' => '質問',
        ]);

        $response = $this->post('/contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都新宿区',
            'building' => 'テスト',
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
            'detail' => 'テストです。',
        ]);

        $response->assertRedirect('/thanks');

        $this->assertDatabaseHas('contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'category_id' => $category->id,
        ]);

        $contact = Contact::where('email', 'test@example.com')->firstOrFail();

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_contact_store_validation_error(): void
    {
        $response = $this->post('/contacts', [
            'first_name' => '',
            'last_name' => '',
            'gender' => '',
            'email' => 'invalid-email',
            'tel' => '123',
            'address' => '',
            'category_id' => '',
            'detail' => '',
        ]);

        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);

        $this->assertDatabaseCount('contacts', 0);
    }
}
