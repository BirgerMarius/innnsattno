<?php

namespace App\Services\News;

use App\NewsArticle;
use App\NewsSource;
use App\Services\News\Parsers\Corrections1HtmlParser;
use App\Services\News\Parsers\SekoHtmlParser;
use App\Services\News\Parsers\XmlFeedParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class NewsFeedService
{
    private $normalizer; private $parsers;
    public function __construct(UrlNormalizer $normalizer, XmlFeedParser $xml, Corrections1HtmlParser $corrections, SekoHtmlParser $seko)
    { $this->normalizer=$normalizer; $this->parsers=[$xml,$corrections,$seko]; }

    public function fetchActive(?string $slug = null): array
    {
        $query=NewsSource::where('is_active',true); if($slug) $query->where('slug',$slug);
        return $query->orderBy('name')->get()->map(function($source){ return $this->fetch($source); })->all();
    }

    public function fetch(NewsSource $source): array
    {
        $report=['source'=>$source->name,'slug'=>$source->slug,'found'=>0,'new'=>0,'duplicates'=>0,'error'=>null];
        $source->update(['last_fetched_at'=>now()]);
        try {
            $parser=collect($this->parsers)->first(function($p) use($source){ return $p->supports($source); });
            if(!$parser) throw new \RuntimeException('Ingen parser er konfigurert for kilden.');
            $url=$source->feed_url ?: $source->website_url;
            $response=Http::timeout(15)->connectTimeout(5)->withHeaders(['User-Agent'=>'innsatt.no news fetcher/1.0 (+https://innsatt.no)','Accept'=>'application/rss+xml, application/atom+xml, application/xml, text/html;q=0.8'])->get($url);
            $response->throw(); $items=$parser->parse($response->body(),$source); $report['found']=count($items);
            foreach($items as $item) {
                $clean=$this->clean($item); if(!$clean) continue;
                $duplicate=$clean['external_id'] ? NewsArticle::where('news_source_id',$source->id)->where('external_id',$clean['external_id'])->exists() : false;
                $duplicate=$duplicate || NewsArticle::where('news_source_id',$source->id)->where('normalized_url_hash',NewsArticle::normalizedUrlHash($clean['normalized_url']))->exists();
                if($duplicate){$report['duplicates']++; continue;}
                NewsArticle::create($clean+['news_source_id'=>$source->id,'status'=>NewsArticle::STATUS_PENDING,'fetched_at'=>now()]); $report['new']++;
            }
            $source->update(['last_success_at'=>now(),'last_error'=>null]);
        } catch(Throwable $e) {
            $message=mb_substr($e->getMessage(),0,2000); $source->update(['last_error'=>$message]); $report['error']=$message;
            Log::warning('Nyhetshenting feilet', ['source'=>$source->slug,'error'=>$message]);
        }
        return $report;
    }

    private function clean(array $item): ?array
    {
        $title=trim(strip_tags((string)($item['title']??''))); $normalized=$this->normalizer->normalize((string)($item['url']??''));
        if($title==='' || !$normalized) return null;
        $image=$this->normalizer->normalize((string)($item['image_url']??''));
        return ['external_id'=>($item['external_id']??null) ?: null,'original_url'=>$normalized,'normalized_url'=>$normalized,'original_title'=>$title,
            'original_excerpt'=>$this->plain($item['excerpt']??null),'original_author'=>$this->plain($item['author']??null),
            'image_url'=>$image,'published_at'=>$this->date($item['published_at']??null)];
    }
    private function plain($value): ?string { if($value===null)return null; $value=trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags((string)$value),ENT_QUOTES|ENT_HTML5,'UTF-8'))); return $value!==''?$value:null; }
    private function date($value) { if(!$value)return null; try{return Carbon::parse($value);}catch(Throwable $e){return null;} }
}
