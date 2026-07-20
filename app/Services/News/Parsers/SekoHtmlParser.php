<?php

namespace App\Services\News\Parsers;

use App\NewsSource;
use DOMDocument;
use DOMXPath;
use RuntimeException;

class SekoHtmlParser implements ParserInterface
{
    private const CORRECTIONS_TERMS = [
        'kriminalvård',
        'kriminalvården',
        'kriminalvårdare',
        'kriminalvårdsanstalt',
        'anstalt',
        'anstalten',
        'häkte',
        'häktet',
        'häkten',
        'frihetsberövad',
        'frihetsberövade',
        'fängelse',
        'fängelser',
        'intagen',
        'intagna',
        'klient',
        'klienter',
    ];

    public function supports(NewsSource $source): bool { return $source->slug === 'seko-kriminalvarden'; }
    public function parse(string $content, NewsSource $source): array
    {
        $d=new DOMDocument(); $p=libxml_use_internal_errors(true); $ok=$d->loadHTML('<?xml encoding="UTF-8">'.$content, LIBXML_NONET|LIBXML_NOWARNING|LIBXML_NOERROR); libxml_clear_errors(); libxml_use_internal_errors($p);
        if(!$ok) throw new RuntimeException('Kilden returnerte ugyldig HTML.'); $x=new DOMXPath($d); $result=[];
        foreach ($x->query("//article[.//a[contains(@href,'/nyheter/')][.//h3]]") as $container) {
            $link=$x->query(".//a[contains(@href,'/nyheter/')][.//h3]",$container)->item(0);
            $title=trim($x->query('.//h3',$link)->item(0)->textContent); $href=$link->getAttribute('href');
            if (strpos($href,'http')!==0) $href='https://www.seko.se/'.ltrim($href,'/');
            $date=$x->query('.//time | preceding-sibling::time[1] | preceding-sibling::p[1]',$container)->item(0);
            $excerpt=$x->query('.//p',$container)->item(0);
            $article=['external_id'=>null,'url'=>$href,'title'=>$title,'excerpt'=>$excerpt?trim($excerpt->textContent):null,'author'=>null,'image_url'=>null,'published_at'=>$date?trim($date->textContent):null];
            if ($this->isRelevantSekoArticle($article)) $result[]=$article;
        }
        return $result;
    }

    public function isRelevantSekoArticle(array $article): bool
    {
        $url=rawurldecode((string)($article['url'] ?? ''));
        $text=implode(' ', [
            (string)($article['title'] ?? ''),
            (string)($article['excerpt'] ?? ''),
            $url,
        ]);

        foreach (self::CORRECTIONS_TERMS as $term) {
            if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($term, '/').'(?![\p{L}\p{N}])/iu', $text)) return true;
        }

        // Swedish letters are commonly transliterated in URL slugs.
        return (bool)preg_match('/(?:^|[^a-z0-9])kriminalvard(?:en)?(?:[^a-z0-9]|$)/i', $url);
    }
}
