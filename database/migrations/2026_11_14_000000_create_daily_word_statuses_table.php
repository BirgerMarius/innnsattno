<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyWordStatusesTable extends Migration
{
    public function up()
    {
        Schema::create('daily_word_statuses', function (Blueprint $table) {
            $table->string('word_id')->primary();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_word_statuses');
    }
}
