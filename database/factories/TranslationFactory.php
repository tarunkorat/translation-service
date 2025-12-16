<?php

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ko'];
        $keys = [
            'app.name',
            'auth.login',
            'auth.logout',
            'auth.register',
            'common.save',
            'common.cancel',
            'common.delete',
            'common.edit',
            'messages.success',
            'messages.error',
            'navigation.home',
            'navigation.about',
            'navigation.contact',
            'buttons.submit',
            'buttons.back',
            'validation.required',
            'validation.email',
            'forms.name',
            'forms.email',
            'forms.password',
        ];

        return [
            'key' => 'test.key.' . $this->faker->unique()->uuid(),
            'locale' => fake()->randomElement($locales),
            'content' => fake()->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
