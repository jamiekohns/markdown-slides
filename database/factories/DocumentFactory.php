<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Document $document): void {
            $document->slides()->create([
                'sort_order' => 1,
                'content' => implode("\n", [
                    '# '.fake()->sentence(3),
                    '',
                    fake()->paragraph(2),
                ]),
            ]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'user_id' => User::factory(),
            'theme_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 999999),
            'description' => fake()->optional()->sentence(10),
        ];
    }
}
