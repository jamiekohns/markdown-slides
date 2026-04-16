<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Script;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Script>
 */
class ScriptFactory extends Factory
{
    protected $model = Script::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'content' => implode("\n", [
                '# Presenter notes',
                '',
                'Intro section.',
                '<x-slidewire::slide>',
                'Main talking points.',
            ]),
        ];
    }
}
