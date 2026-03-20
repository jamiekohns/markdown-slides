<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(10),
            'css' => implode("\n", [
                '.slidewire-content h1 {',
                '  letter-spacing: 0.02em;',
                '}',
                '',
                '.slidewire-content a {',
                '  text-decoration: underline;',
                '}',
            ]),
        ];
    }
}
