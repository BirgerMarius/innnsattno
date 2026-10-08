<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateNffMagasinetNewsSource extends Migration
{
    public function up()
    {
        DB::table('news_sources')->where('slug', 'nff-magasinet')->update([
            'website_url' => 'https://www.frifagbevegelse.no/nff-magasinet',
            'feed_url' => 'https://www.frifagbevegelse.no/nff-magasinet/?lab_viewport=rss',
            'source_type' => 'rss',
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        DB::table('news_sources')->where('slug', 'nff-magasinet')->update([
            'website_url' => 'https://frifagbevegelse.no/nffmagasinet-6.226.1175.7717127fa1',
            'feed_url' => 'https://frifagbevegelse.no/nyheter-6.295.164.0.11fb3b69c7',
            'updated_at' => now(),
        ]);
    }
}
