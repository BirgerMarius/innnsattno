<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeedbackSubmissionsTable extends Migration
{
    public function up()
    {
        Schema::create('feedback_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('title', 150);
            $table->text('message');
            $table->boolean('is_anonymous')->default(true);
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('feedback_submissions');
    }
}
