<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_tags(): void
    {
        $response = $this->get('/admin/tags/1/edit');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_create_tag(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/tags', [
            'name' => 'テストタグ',
        ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'name' => 'テストタグ',
        ]);
    }

    public function test_admin_can_edit_tag(): void
    {
        $user = User::factory()->create();

        $tag = Tag::create([
            'name' => '変更前',
        ]);

        $response = $this->actingAs($user)
            ->put("/admin/tags/{$tag->id}", [
                'name' => '変更後',
            ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '変更後',
        ]);
    }

    public function test_admin_can_delete_tag(): void
    {
        $user = User::factory()->create();

        $tag = Tag::create([
            'name' => '削除対象',
        ]);

        $response = $this->actingAs($user)
            ->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/admin');

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_tag_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/tags', [
            'name' => '',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'タグ名を入力してください',
        ]);
    }

    public function test_tag_name_must_be_50_characters_or_less(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/tags', [
            'name' => str_repeat('あ', 51),
        ]);

        $response->assertSessionHasErrors([
            'name' => 'タグ名は 50文字以内で入力してください',
        ]);
    }

    public function test_tag_name_must_be_unique(): void
    {
        $user = User::factory()->create();

        Tag::create([
            'name' => '重複タグ',
        ]);

        $response = $this->actingAs($user)->post('/admin/tags', [
            'name' => '重複タグ',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'そのタグ名は既に使用されています',
        ]);
    }

    public function test_tag_can_keep_its_current_name_when_updated(): void
    {
        $user = User::factory()->create();

        $tag = Tag::create([
            'name' => '既存タグ',
        ]);

        $response = $this->actingAs($user)
            ->put("/admin/tags/{$tag->id}", [
                'name' => '既存タグ',
            ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '既存タグ',
        ]);
    }
}
