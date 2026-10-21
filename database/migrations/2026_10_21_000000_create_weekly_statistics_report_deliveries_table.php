<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeeklyStatisticsReportDeliveriesTable extends Migration
{
    public function up()
    {
        Schema::create('weekly_statistics_report_deliveries', function (Blueprint $table) {
            $table->id();
            $table->date('week_starts_on')->unique();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('weekly_statistics_report_deliveries');
    }
}
