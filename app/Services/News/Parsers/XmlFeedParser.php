<?php

namespace App\Services\News\Parsers;

use App\NewsSource;
use RuntimeException;

class XmlFeedParser implements ParserInterface
{
    public function supports(NewsSource $source): bool { return in_array($source->source_type, ['rss','atom'], true); }

    public function parse(string $content, NewsSource $source): array
    {
        $previous = libxml_use_internal_errors(true); $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        if ($xml === false) throw new RuntimeException('Kilden returnerte ugyldig XML.');
        $isRss = isset($xml->channel); $items = $isRss ? $xml->channel->item : $xml->xpath('/*[local-name()="feed"]/*[local-name()="entry"]'); $result = [];
        foreach ($items as $item) {
            $url = $isRss ? (string) $item->link : $this->xpathValue($item, './*[local-name()="link"]/@href');
            if ($source->slug === 'nff-magasinet' && strpos($url, '/nffmagasinet/') === false) continue;
            $media = $item->children('http://search.yahoo.com/mrss/');
            $result[] = ['external_id'=>$isRss ? ((string)$item->guid ?: null) : ($this->xpathValue($item,'./*[local-name()="id"]') ?: null), 'url'=>$url, 'title'=>$isRss ? (string)$item->title : $this->xpathValue($item,'./*[local-name()="title"]'),
                'excerpt'=>$isRss ? (string)$item->description : $this->xpathValue($item,'./*[local-name()="summary"]'), 'author'=>$this->author($item),
                'image_url'=>isset($media->content) ? (string) $media->content->attributes()->url : null,
                'published_at'=>$isRss ? ((string)$item->pubDate ?: null) : ($this->xpathValue($item,'./*[local-name()="published"] | ./*[local-name()="updated"]') ?: null)];
        }
        return $result;
    }

    private function author($item): ?string
    {
        $dc = $item->children('http://purl.org/dc/elements/1.1/');
        $author = (string) ($dc->creator ?? '');
        if (! $author) $author = $this->xpathValue($item, './*[local-name()="author"]/*[local-name()="name"]');
        return $author ?: null;
    }
    private function xpathValue($node, string $query): string { $values=$node->xpath($query); return $values ? trim((string)$values[0]) : ''; }
}
