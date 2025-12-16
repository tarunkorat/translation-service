<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191)->index();
            $table->string('locale', 10)->index();
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();

            // Composite unique index for key + locale
            $table->unique(['key', 'locale']);

            // Index for faster queries
            $table->index(['key', 'locale', 'deleted_at']);
            $table->index('created_at');
        });

        // FULLTEXT only for MySQL / MariaDB
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE translations ADD FULLTEXT translations_content_fulltext (content)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
