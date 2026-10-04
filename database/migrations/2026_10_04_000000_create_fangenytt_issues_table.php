<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFangenyttIssuesTable extends Migration
{
    public function up()
    {
        Schema::create('fangenytt_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('number')->unique();
            $table->string('edition', 100)->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('original_url', 2048);
            $table->string('local_file', 255);
            $table->string('cover_file', 255);
            $table->string('source', 50);
            $table->string('status', 20)->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fangenytt_issues');
    }
}
