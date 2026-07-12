<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMediaTypeToProfessionalResourcesTable extends Migration
{
    public function up()
    {
        Schema::table('professional_resources', function (Blueprint $table) {
            $table->string('media_type', 40)->nullable()->after('content_type')->index();
        });
    }

    public function down()
    {
        Schema::table('professional_resources', function (Blueprint $table) {
            $table->dropColumn('media_type');
        });
    }
}
