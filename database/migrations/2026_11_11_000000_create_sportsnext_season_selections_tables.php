<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSportsnextSeasonSelectionsTables extends Migration
{
    public function up()
    {
        Schema::create('sportsnext_season_selections', function (Blueprint $table) {
            $table->id();
            $table->string('competition_key', 80)->unique();
            $table->unsignedBigInteger('selected_season_id')->nullable();
            $table->boolean('automatic_enabled')->default(false);
            $table->unsignedBigInteger('blocked_candidate_season_id')->nullable();
            $table->unsignedBigInteger('candidate_season_id')->nullable();
            $table->string('candidate_name')->nullable();
            $table->date('candidate_start_date')->nullable();
            $table->date('candidate_end_date')->nullable();
            $table->unsignedInteger('candidate_observations')->default(0);
            $table->timestamp('candidate_first_observed_at')->nullable();
            $table->timestamp('candidate_last_observed_at')->nullable();
            $table->unsignedBigInteger('api_current_season_id')->nullable();
            $table->string('api_current_season_name')->nullable();
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->string('status', 40)->default('Ikke kontrollert');
            $table->text('status_reason')->nullable();
            $table->timestamp('notice_read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('sportsnext_season_switches', function (Blueprint $table) {
            $table->id();
            $table->string('competition_key', 80);
            $table->unsignedBigInteger('from_season_id')->nullable();
            $table->unsignedBigInteger('to_season_id');
            $table->string('method', 30);
            $table->string('reason');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sportsnext_season_switches');
        Schema::dropIfExists('sportsnext_season_selections');
    }
}
