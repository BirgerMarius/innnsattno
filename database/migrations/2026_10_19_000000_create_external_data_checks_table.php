<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExternalDataChecksTable extends Migration
{
    public function up()
    {
        Schema::create('external_data_checks', function (Blueprint $table) {
            $table->id();
            $table->string('service', 100);
            $table->string('coverage_key', 190);
            $table->string('datatype', 40);
            $table->string('description');
            $table->boolean('essential')->default(false);
            $table->boolean('usable')->default(false);
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_check_at')->nullable();
            $table->timestamp('first_failure_at')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['service', 'coverage_key']);
        });
        Schema::create('external_data_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('service', 100)->unique();
            $table->json('affected_keys');
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('external_data_incidents');
        Schema::dropIfExists('external_data_checks');
    }
}
