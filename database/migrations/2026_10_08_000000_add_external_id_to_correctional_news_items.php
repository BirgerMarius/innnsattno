<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddExternalIdToCorrectionalNewsItems extends Migration { public function up() { Schema::table('correctional_news_items', function (Blueprint $table) { $table->string('external_id', 100)->nullable()->after('source_key')->index(); }); } public function down() { Schema::table('correctional_news_items', function (Blueprint $table) { $table->dropIndex(['external_id']); $table->dropColumn('external_id'); }); } }
