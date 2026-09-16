<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorrectionalNewsItemsTable extends Migration
{
    public function up()
    {
        Schema::create('correctional_news_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correctional_news_cluster_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_key', 50)->index();
            $table->string('source_name', 100);
            $table->text('original_title');
            $table->string('normalized_title', 1000);
            $table->string('original_url', 2048);
            $table->string('normalized_url', 2048);
            $table->char('normalized_url_hash', 64)->unique();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('fetched_at');
            $table->unsignedSmallInteger('relevance_score')->default(0);
            $table->boolean('is_subscription')->default(false);
            $table->json('labels')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('correctional_news_items');
    }
}
