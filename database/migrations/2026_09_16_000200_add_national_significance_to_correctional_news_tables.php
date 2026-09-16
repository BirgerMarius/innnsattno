<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNationalSignificanceToCorrectionalNewsTables extends Migration
{
    public function up()
    {
        Schema::table('correctional_news_clusters', function (Blueprint $table) {
            $table->unsignedSmallInteger('national_significance_score')->default(0)->after('relevance_score')->index();
        });
        Schema::table('correctional_news_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('national_significance_score')->default(0)->after('relevance_score');
            $table->string('content_type', 30)->default('news')->after('national_significance_score')->index();
        });
    }

    public function down()
    {
        Schema::table('correctional_news_items', function (Blueprint $table) { $table->dropColumn(['national_significance_score', 'content_type']); });
        Schema::table('correctional_news_clusters', function (Blueprint $table) { $table->dropColumn('national_significance_score'); });
    }
}
