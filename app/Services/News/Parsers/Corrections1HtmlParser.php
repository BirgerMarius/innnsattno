<?php

namespace App\Services\News\Parsers;

use App\NewsSource;
use DOMDocument;
use DOMXPath;
use RuntimeException;

class Corrections1HtmlParser implements ParserInterface
{
    public function supports(NewsSource $source): bool { return $source->slug === 'corrections1'; }
    public function parse(string $content, NewsSource $source): array
    {
        [$xpath] = $this->document($content); $result = [];
        foreach ($xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' PagePromo ')][.//*[contains(concat(' ',normalize-space(@class),' '),' PagePromo-title ')]]") as $promo) {
            $link = $xpath->query(".//*[contains(concat(' ',normalize-space(@class),' '),' PagePromo-title ')]//a", $promo)->item(0);
            if (! $link) continue;
            $result[] = ['external_id'=>null,'url'=>$link->getAttribute('href'),'title'=>trim($link->textContent),
                'excerpt'=>$this->text($xpath, ".//*[contains(concat(' ',normalize-space(@class),' '),' PagePromo-description ')]", $promo),
                'author'=>$this->text($xpath, ".//*[contains(concat(' ',normalize-space(@class),' '),' PagePromo-author ')]", $promo),
                'image_url'=>$this->attribute($xpath, './/img', 'src', $promo),
                'published_at'=>$this->text($xpath, ".//*[contains(concat(' ',normalize-space(@class),' '),' PagePromo-date ')]", $promo)];
        }
        return $result;
    }
    private function document(string $html): array { $d=new DOMDocument(); $p=libxml_use_internal_errors(true); $ok=$d->loadHTML($html, LIBXML_NONET|LIBXML_NOWARNING|LIBXML_NOERROR); libxml_clear_errors(); libxml_use_internal_errors($p); if(!$ok) throw new RuntimeException('Kilden returnerte ugyldig HTML.'); return [new DOMXPath($d),$d]; }
    private function text(DOMXPath $x,string $q,$n): ?string { $v=$x->query($q,$n)->item(0); return $v ? trim($v->textContent) : null; }
    private function attribute(DOMXPath $x,string $q,string $a,$n): ?string { $v=$x->query($q,$n)->item(0); return $v ? $v->getAttribute($a) : null; }
}
