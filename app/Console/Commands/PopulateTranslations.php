<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:populate {count=100000 : Number of translations to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the database with test translation data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->argument('count');

        $this->info("Starting to populate {$count} translations...");

        $startTime = microtime(true);

        // Create tags first
        $this->info('Creating tags...');
        $tags = $this->createTags();

        // Create translations in chunks
        $this->info('Creating translations...');
        $this->createTranslations($count, $tags);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->info("Successfully created {$count} translations in {$duration} seconds!");

        return self::SUCCESS;
    }

    /**
     * Create tags.
     */
    protected function createTags(): array
    {
        $tagNames = ['mobile', 'desktop', 'web', 'api', 'admin', 'frontend', 'backend', 'email', 'notification', 'dashboard'];
        $tags = [];

        foreach ($tagNames as $name) {
            $tags[] = Tag::firstOrCreate(
                ['slug' => $name],
                [
                    'name' => ucfirst($name),
                    'description' => "Translations for {$name} context",
                ]
            );
        }

        return $tags;
    }

    /**
     * Create translations.
     */
    protected function createTranslations(int $count, array $tags): void
    {
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ko'];
        $keyPrefixes = [
            'app',
            'auth',
            'common',
            'messages',
            'navigation',
            'buttons',
            'validation',
            'forms',
            'errors',
            'success',
            'pages',
            'components',
        ];

        $chunkSize = 1000;
        $chunks = ceil($count / $chunkSize);
        $bar = $this->output->createProgressBar($chunks);

        DB::beginTransaction();

        try {
            for ($i = 0; $i < $chunks; $i++) {
                $translations = [];
                $currentChunkSize = min($chunkSize, $count - ($i * $chunkSize));

                for ($j = 0; $j < $currentChunkSize; $j++) {
                    $prefix = $keyPrefixes[array_rand($keyPrefixes)];
                    $baseKey = "{$prefix}." . fake()->unique()->slug(3);

                    foreach ($locales as $locale) {
                        $translations[] = [
                            'key' => $baseKey,
                            'locale' => $locale,
                            'content' => fake()->sentence(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                Translation::insertOrIgnore($translations);

                // 👇 reset uniqueness after each chunk
                fake()->unique(true);

                // Attach random tags to some translations
                if ($i % 10 === 0) {
                    $recentTranslations = Translation::latest()
                        ->limit($currentChunkSize)
                        ->get();

                    foreach ($recentTranslations as $translation) {
                        if (rand(0, 100) < 70) {
                            $randomTags = collect($tags)
                                ->random(rand(1, 3))
                                ->pluck('id')
                                ->toArray();

                            $translation->tags()->attach($randomTags);
                        }
                    }
                }

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to populate translations: ' . $e->getMessage());
            throw $e;
        }
    }
}
