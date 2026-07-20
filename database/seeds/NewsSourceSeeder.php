<?php

namespace Database\Seeders;

use App\NewsSource;
use Illuminate\Database\Seeder;

class NewsSourceSeeder extends Seeder
{
    public function run()
    {
        foreach ([
            ['name'=>'NFF-magasinet / FriFagbevegelse','slug'=>'nff-magasinet','country'=>'Norge','website_url'=>'https://frifagbevegelse.no/nffmagasinet-6.226.1175.7717127fa1','feed_url'=>'https://frifagbevegelse.no/nyheter-6.295.164.0.11fb3b69c7','source_type'=>'rss'],
            ['name'=>'Fængselsforbundet','slug'=>'faengselsforbundet','country'=>'Danmark','website_url'=>'https://faengselsforbundet.dk/','feed_url'=>'https://faengselsforbundet.dk/feed/','source_type'=>'rss'],
            ['name'=>'Seko Kriminalvården','slug'=>'seko-kriminalvarden','country'=>'Sverige','website_url'=>'https://www.seko.se/branscher/vard/','feed_url'=>null,'source_type'=>'html'],
            ['name'=>'Corrections1 Original Content','slug'=>'corrections1','country'=>'Internasjonalt','website_url'=>'https://www.corrections1.com/','feed_url'=>'https://www.corrections1.com/original-content-rss','source_type'=>'html'],
        ] as $source) NewsSource::updateOrCreate(['slug'=>$source['slug']], $source + ['is_active'=>true]);
    }
}
