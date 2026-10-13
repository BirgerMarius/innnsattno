<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExternalDataSourceEventsTable extends Migration
{
    public function up()
    {
        Schema::create('external_data_source_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('service', 100);
            $table->string('operation', 100);
            $table->string('outcome', 20);
            $table->string('failure_kind', 100)->nullable();
            $table->unsignedSmallInteger('status')->nullable();
            $table->string('summary', 500)->nullable();
            $table->json('diagnostics')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['outcome', 'occurred_at']);
            $table->index(['service', 'operation', 'occurred_at']);
        });

        Schema::create('external_data_failure_digests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('digest_key', 32)->unique();
            $table->timestamp('period_starts_at');
            $table->timestamp('period_ends_at');
            $table->string('status', 20);
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('external_data_failure_digests');
        Schema::dropIfExists('external_data_source_events');
    }
}
