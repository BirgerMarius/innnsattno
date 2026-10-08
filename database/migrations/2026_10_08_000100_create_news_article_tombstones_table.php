<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateNewsArticleTombstonesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('news_article_tombstones')) {
            Schema::create('news_article_tombstones', function (Blueprint $table) {
                $this->addColumns($table);
            });
        } else {
            $columns = Schema::getColumnListing('news_article_tombstones');
            Schema::table('news_article_tombstones', function (Blueprint $table) use ($columns) {
                if (! in_array('id', $columns, true)) $table->bigIncrements('id');
                if (! in_array('news_source_id', $columns, true)) $table->unsignedBigInteger('news_source_id');
                if (! in_array('external_id', $columns, true)) $table->string('external_id', 512)->nullable();
                if (! in_array('normalized_url_hash', $columns, true)) $table->char('normalized_url_hash', 64);
                if (! in_array('processed_at', $columns, true)) $table->timestamp('processed_at')->nullable();
            });
        }

        $this->addIndexesAndForeignKey();
    }

    public function down()
    {
        Schema::dropIfExists('news_article_tombstones');
    }

    private function addColumns(Blueprint $table): void
    {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('news_source_id');
        $table->string('external_id', 512)->nullable();
        $table->char('normalized_url_hash', 64);
        $table->timestamp('processed_at');
    }

    private function addIndexesAndForeignKey(): void
    {
        $missing = [
            'news_tombstones_processed_idx' => ! $this->hasIndex('news_tombstones_processed_idx'),
            'news_tombstones_source_external_uq' => ! $this->hasIndex('news_tombstones_source_external_uq'),
            'news_tombstones_source_urlhash_uq' => ! $this->hasIndex('news_tombstones_source_urlhash_uq'),
        ];

        if (in_array(true, $missing, true)) {
            Schema::table('news_article_tombstones', function (Blueprint $table) use ($missing) {
                if ($missing['news_tombstones_processed_idx']) $table->index('processed_at', 'news_tombstones_processed_idx');
                if ($missing['news_tombstones_source_external_uq']) $table->unique(['news_source_id', 'external_id'], 'news_tombstones_source_external_uq');
                if ($missing['news_tombstones_source_urlhash_uq']) $table->unique(['news_source_id', 'normalized_url_hash'], 'news_tombstones_source_urlhash_uq');
            });
        }

        if (! $this->hasForeignKey()) {
            Schema::table('news_article_tombstones', function (Blueprint $table) {
                $table->foreign('news_source_id', 'news_tombstones_source_fk')->references('id')->on('news_sources')->onDelete('cascade');
            });
        }
    }

    private function hasIndex(string $name): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('news_article_tombstones')"))->contains(fn ($index) => $index->name === $name);
        }

        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'news_article_tombstones')
            ->where('index_name', $name)
            ->exists();
    }

    private function hasForeignKey(): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return count(DB::select("PRAGMA foreign_key_list('news_article_tombstones')")) > 0;
        }

        return DB::table('information_schema.table_constraints')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'news_article_tombstones')
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
}
