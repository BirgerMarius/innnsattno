<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsSourcesTable extends Migration
{
    public function up()
    {
        Schema::create('news_sources', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('country', 30)->index();
            $table->string('website_url', 2048); $table->string('feed_url', 2048)->nullable(); $table->string('source_type', 10);
            $table->boolean('is_active')->default(true)->index(); $table->timestamp('last_fetched_at')->nullable();
            $table->timestamp('last_success_at')->nullable(); $table->text('last_error')->nullable(); $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('news_sources'); }
}
