<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsArticleTombstonesTable extends Migration
{
    public function up()
    {
        Schema::create('news_article_tombstones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_source_id')->constrained()->cascadeOnDelete();
            $table->string('external_id', 512)->nullable();
            $table->char('normalized_url_hash', 64);
            $table->timestamp('processed_at')->index();
            $table->unique(['news_source_id', 'external_id']);
            $table->unique(['news_source_id', 'normalized_url_hash']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('news_article_tombstones');
    }
}
