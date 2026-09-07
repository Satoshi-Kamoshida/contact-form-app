<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $contact = Contact::create([
                'category_id' => Category::inRandomOrder()->first()->id,
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'gender' => fake()->numberBetween(1, 3),
                'email' => fake()->email(),
                'tel' => fake()->numerify('###########'),
                'address' => fake()->address(),
                'building' => fake()->company(),
                'detail' => fake()->text(120),
            ]);

            $tagIds = Tag::inRandomOrder()->limit(fake()->numberBetween(1, 3))->pluck('id');
            $contact->tags()->attach($tagIds);
        }
    }
}
