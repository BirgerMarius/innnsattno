<?php

namespace App\Console\Commands;

use App\Services\News\NewsArticleRetentionService;
use Illuminate\Console\Command;

class PruneNewsArticles extends Command
{
    protected $signature = 'news:prune';

    protected $description = 'Sletter utløpte nyhetsartikler og beholder bare duplikatidentifikatorer';

    public function handle(NewsArticleRetentionService $retention): int
    {
        $deleted = $retention->prune();
        $this->info("Slettet {$deleted} utløpte nyhetsartikler.");

        return self::SUCCESS;
    }
}
