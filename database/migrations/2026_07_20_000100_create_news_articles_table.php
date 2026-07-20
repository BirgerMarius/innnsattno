<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsArticlesTable extends Migration
{
    public function up()
    {
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id(); $table->foreignId('news_source_id')->constrained()->cascadeOnDelete(); $table->string('external_id', 512)->nullable();
            $table->string('original_url', 2048); $table->string('normalized_url', 2048); $table->char('normalized_url_hash', 64); $table->text('original_title');
            $table->text('original_excerpt')->nullable(); $table->string('original_author')->nullable(); $table->string('image_url', 2048)->nullable();
            $table->timestamp('published_at')->nullable()->index(); $table->timestamp('fetched_at'); $table->string('status', 20)->default('pending')->index();
            $table->text('edited_title')->nullable(); $table->text('edited_excerpt')->nullable(); $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->unique(['news_source_id', 'external_id']); $table->unique(['news_source_id', 'normalized_url_hash']);
        });
    }
    public function down() { Schema::dropIfExists('news_articles'); }
}
