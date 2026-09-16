<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorrectionalNewsClustersTable extends Migration
{
    public function up()
    {
        Schema::create('correctional_news_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('category', 20)->index();
            $table->text('display_title');
            $table->string('primary_source', 100);
            $table->string('primary_url', 2048);
            $table->timestamp('primary_published_at')->nullable()->index();
            $table->boolean('primary_is_subscription')->default(false);
            $table->unsignedSmallInteger('relevance_score')->default(0);
            $table->unsignedSmallInteger('source_count')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('correctional_news_clusters');
    }
}
