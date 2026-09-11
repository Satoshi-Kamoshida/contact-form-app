<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_contact_data(): void
    {

        $category = Category::create([
            'content' => '商品の交換について',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => null,
            'detail' => 'お問い合わせ内容です',
        ]);

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
    }

    public function test_can_filter_contacts_by_keyword(): void
    {

        $category = Category::create([
            'content' => '商品の交換について',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => null,
            'detail' => 'お問い合わせ内容です',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'sato@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => null,
            'detail' => 'お問い合わせ内容です',
        ]);

        $response = $this->getJson('/api/v1/contacts?keyword=山田');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'first_name' => '山田',
        ]);

        $response->assertJsonMissing([
            'first_name' => '佐藤',
        ]);
    }

    public function test_can_get_contacts_with_correct_pagination(): void
    {
        $category = Category::create([
            'content' => '商品の交換について',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => null,
            'detail' => 'お問い合わせ内容です',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'sato@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => null,
            'detail' => 'お問い合わせ内容です',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '鈴木',
            'last_name' => '一郎',
            'gender' => 3,
            'email' => 'suzuki@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => null,
            'detail' => 'お問い合わせ内容です',
        ]);

        $response = $this->getJson('/api/v1/contacts?per_page=2');

        $response->assertStatus(200);

        $response->assertJsonCount(2, 'data');

        $response->assertJsonPath('meta.per_page', 2);
    }

    public function test_contacts_can_be_filtered_by_gender(): void
    {
        $category = Category::create([
            'content' => 'テストカテゴリー',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'male@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'detail' => '男性のお問い合わせ',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '花子',
            'last_name' => '佐藤',
            'gender' => 2,
            'email' => 'female@example.com',
            'tel' => '08012345678',
            'address' => '大阪府',
            'detail' => '女性のお問い合わせ',
        ]);

        $response = $this->getJson('/api/v1/contacts?gender=1');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'last_name' => '山田',
        ]);

        $response->assertJsonFragment([
            'email' => 'male@example.com',
        ]);

        $response->assertJsonMissing([
            'last_name' => '佐藤',
        ]);

        $response->assertJsonMissing([
            'email' => 'female@example.com',
        ]);
    }

    public function test_contacts_can_be_filtered_by_category(): void
    {
        $categoryA = Category::create([
            'content' => 'テストカテゴリーA',
        ]);

        $categoryB = Category::create([
            'content' => 'テストカテゴリーB',
        ]);

        Contact::create([
            'category_id' => $categoryA->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'male@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'detail' => '男性のお問い合わせ',
        ]);

        Contact::create([
            'category_id' => $categoryB->id,
            'first_name' => '花子',
            'last_name' => '佐藤',
            'gender' => 2,
            'email' => 'female@example.com',
            'tel' => '08012345678',
            'address' => '大阪府',
            'detail' => '女性のお問い合わせ',
        ]);

        $response = $this->getJson(
            '/api/v1/contacts?category_id='.$categoryA->id
        );

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'last_name' => '山田',
        ]);

        $response->assertJsonFragment([
            'email' => 'male@example.com',
        ]);

        $response->assertJsonMissing([
            'last_name' => '佐藤',
        ]);

        $response->assertJsonMissing([
            'email' => 'female@example.com',
        ]);
    }

    public function test_contacts_can_be_filtered_by_date(): void
    {
        $category = Category::create([
            'content' => 'テストカテゴリー',
        ]);

        $contact1 = Contact::create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'detail' => '指定日のお問い合わせ',
        ]);

        $contact1->created_at = Carbon::parse('2026-09-10 10:00:00');
        $contact1->save();

        $contact2 = Contact::create([
            'category_id' => $category->id,
            'first_name' => '花子',
            'last_name' => '佐藤',
            'gender' => 2,
            'email' => 'sato@example.com',
            'tel' => '08012345678',
            'address' => '大阪府',
            'detail' => '別日のお問い合わせ',
        ]);

        $contact2->created_at = Carbon::parse('2026-09-09 10:00:00');
        $contact2->save();

        $response = $this->getJson('/api/v1/contacts?date=2026-09-10');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'last_name' => '山田',
        ]);

        $response->assertJsonFragment([
            'email' => 'yamada@example.com',
        ]);

        $response->assertJsonMissing([
            'last_name' => '佐藤',
        ]);

        $response->assertJsonMissing([
            'email' => 'sato@example.com',
        ]);

    }

    public function test_per_page_must_not_exceed_100(): void
    {
        $response = $this->getJson('/api/v1/contacts?per_page=101');

        $response->assertStatus(422);

        $response->assertJsonValidationErrors('per_page');
    }

    public function test_gender_must_be_valid(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=0');

        $response->assertStatus(422);

        $response->assertJsonValidationErrors('gender');
    }

    public function test_category_id_must_exist(): void
    {
        $response = $this->getJson('/api/v1/contacts?category_id=999');

        $response->assertStatus(422);

        $response->assertJsonValidationErrors('category_id');
    }

    public function test_date_must_be_valid(): void
    {
        $response = $this->getJson('/api/v1/contacts?date=invalid-date');

        $response->assertStatus(422);

        $response->assertJsonValidationErrors('date');
    }
}
