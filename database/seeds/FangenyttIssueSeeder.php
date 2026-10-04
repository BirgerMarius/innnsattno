<?php

namespace Database\Seeders;

use App\FangenyttIssue;
use Illuminate\Database\Seeder;

class FangenyttIssueSeeder extends Seeder
{
    public function run()
    {
        foreach (config('fangenytt.issues', []) as $issue) {
            $number = filter_var($issue['number'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($number === false || empty($issue['original_url'])) {
                continue;
            }

            FangenyttIssue::updateOrCreate(['number' => $number], [
                'edition' => $issue['edition'] ?? null,
                'original_url' => $issue['original_url'],
                'local_file' => 'fangenytt-'.$number.'.pdf',
                'cover_file' => 'covers/fangenytt-'.$number.'.jpg',
                'source' => 'legacy-config',
                'status' => FangenyttIssue::STATUS_PUBLISHED,
            ]);
        }
    }
}
