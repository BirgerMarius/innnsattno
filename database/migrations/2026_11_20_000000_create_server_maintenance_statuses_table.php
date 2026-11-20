<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServerMaintenanceStatusesTable extends Migration
{
    public function up()
    {
        Schema::create('server_maintenance_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('state', 32);
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->boolean('reboot_required')->default(false);
            $table->json('packages')->nullable();
            $table->string('fingerprint', 64)->nullable();
            $table->string('last_error', 1000)->nullable();
            $table->boolean('error_alert_sent')->default(false);
            $table->timestamps();
        });
        Schema::create('server_maintenance_mail_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_key')->unique();
            $table->string('kind', 32);
            $table->json('payload');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('server_maintenance_mail_deliveries');
        Schema::dropIfExists('server_maintenance_statuses');
    }
}
