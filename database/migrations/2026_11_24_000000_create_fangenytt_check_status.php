<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFangenyttCheckStatus extends Migration
{
    public function up()
    {
        Schema::create('fangenytt_check_status', function (Blueprint $table) {
            $table->id();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
        Schema::table('fangenytt_issues', function (Blueprint $table) {
            $table->timestamp('published_locally_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('fangenytt_issues', function (Blueprint $table) { $table->dropColumn('published_locally_at'); });
        Schema::dropIfExists('fangenytt_check_status');
    }
}
