<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_export_csv(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'content' => 'テストカテゴリー',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => 'テスト',
            'last_name' => 'ユーザー',
            'gender' => 1,
            'email' => 'test6@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => 'テストビル',
            'detail' => 'お問い合わせテスト',
        ]);

        $response = $this->actingAs($user)
            ->get('/contacts/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader(
            'Content-Disposition',
            'attachment; filename="contacts.csv"'
        );
    }

    public function test_csv_contains_contact_data(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'content' => 'テストカテゴリー',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '悟',
            'last_name' => '鴨志田',
            'gender' => 1,
            'email' => 'duck@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => 'テスト',
            'detail' => 'お問い合わせテスト',
        ]);

        $response = $this->actingAs($user)
            ->get('/contacts/export');

        $content = $response->streamedContent();

        $this->assertStringContainsString('悟', $content);
        $this->assertStringContainsString('鴨志田', $content);
        $this->assertStringContainsString('duck@example.com', $content);
        $this->assertStringContainsString('男性', $content);
        $this->assertStringContainsString('テストカテゴリー', $content);
        $this->assertStringContainsString('お問い合わせテスト', $content);
    }

    public function test_csv_has_bom_and_headers(): void
    {
        $user = User::factory()->create();

        Category::create([
            'content' => 'テストカテゴリー',
        ]);

        $response = $this->actingAs($user)
            ->get('/contacts/export');

        $content = $response->streamedContent();

        // UTF-8 BOMを確認
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // CSVヘッダーを確認
        $header = substr($content, 3);
        $firstLine = strtok($header, "\r\n");

        $this->assertSame(
            'ID,氏名,性別,メール,電話,住所,建物,カテゴリ,内容,作成日時',
            $firstLine
        );
    }

    public function test_csv_export_can_be_filtered_by_keyword(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'content' => 'テストカテゴリー',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'detail' => '山田さんのお問い合わせ',
        ]);

        Contact::create([
            'category_id' => $category->id,
            'first_name' => '花子',
            'last_name' => '佐藤',
            'gender' => 2,
            'email' => 'sato@example.com',
            'tel' => '08012345678',
            'address' => '大阪府',
            'detail' => '佐藤さんのお問い合わせ',
        ]);

        $response = $this->actingAs($user)
            ->get('/contacts/export?keyword=山田');

        $content = $response->streamedContent();

        $this->assertStringContainsString('山田', $content);
        $this->assertStringContainsString('yamada@example.com', $content);

        $this->assertStringNotContainsString('佐藤', $content);
        $this->assertStringNotContainsString('sato@example.com', $content);
    }

    public function test_csv_export_can_be_filtered_by_gender(): void
    {
        $user = User::factory()->create();

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

        $response = $this->actingAs($user)
            ->get('/contacts/export?gender=1');

        $content = $response->streamedContent();

        $this->assertStringContainsString('山田', $content);
        $this->assertStringContainsString('male@example.com', $content);
        $this->assertStringContainsString('男性', $content);

        $this->assertStringNotContainsString('佐藤', $content);
        $this->assertStringNotContainsString('female@example.com', $content);
    }

    public function test_csv_export_can_be_filtered_by_category(): void
    {
        $user = User::factory()->create();

        $category1 = Category::create([
            'content' => '商品について',
        ]);

        $category2 = Category::create([
            'content' => 'その他',
        ]);

        Contact::create([
            'category_id' => $category1->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'detail' => '商品についてのお問い合わせ',
        ]);

        Contact::create([
            'category_id' => $category2->id,
            'first_name' => '花子',
            'last_name' => '佐藤',
            'gender' => 2,
            'email' => 'sato@example.com',
            'tel' => '08012345678',
            'address' => '大阪府',
            'detail' => 'その他のお問い合わせ',
        ]);

        $response = $this->actingAs($user)
            ->get('/contacts/export?category_id='.$category1->id);

        $content = $response->streamedContent();

        $this->assertStringContainsString('山田', $content);
        $this->assertStringContainsString('商品について', $content);
        $this->assertStringContainsString('商品についてのお問い合わせ', $content);

        $this->assertStringNotContainsString('佐藤', $content);
        $this->assertStringNotContainsString('その他のお問い合わせ', $content);
    }

    public function test_csv_export_can_be_filtered_by_date(): void
    {
        $user = User::factory()->create();

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

        $contact1->created_at = '2026-09-10 10:00:00';
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

        $contact2->created_at = '2026-09-09 10:00:00';
        $contact2->save();

        $response = $this->actingAs($user)
            ->get('/contacts/export?date=2026-09-10');

        $content = $response->streamedContent();

        $this->assertStringContainsString('山田', $content);
        $this->assertStringContainsString('指定日のお問い合わせ', $content);

        $this->assertStringNotContainsString('佐藤', $content);
        $this->assertStringNotContainsString('別日のお問い合わせ', $content);
    }

    public function test_csv_export_returns_validation_error_for_invalid_gender(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/contacts/export?gender=5');

        $response->assertStatus(302);
        $response->assertSessionHasErrors('gender');
    }

    public function test_csv_export_returns_validation_error_for_too_long_keyword(): void
    {
        $user = User::factory()->create();

        $keyword = str_repeat('あ', 256);

        $response = $this->actingAs($user)
            ->get('/contacts/export?keyword='.urlencode($keyword));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('keyword');
    }

    public function test_csv_export_returns_validation_error_for_invalid_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/contacts/export?category_id=999999');

        $response->assertStatus(302);
        $response->assertSessionHasErrors('category_id');
    }

    public function test_csv_export_returns_validation_error_for_invalid_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/contacts/export?date=invalid-date');

        $response->assertStatus(302);
        $response->assertSessionHasErrors('date');
    }
}
