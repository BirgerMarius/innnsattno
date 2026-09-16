<?php

namespace App\Services\CorrectionalNews;

use RuntimeException;

abstract class RssNewsSource implements CorrectionalNewsSource
{
    public function parse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($feed === false || ! isset($feed->channel)) {
            throw new RuntimeException('Kilden returnerte ugyldig RSS.');
        }

        $items = [];
        foreach ($feed->channel->item as $item) {
            $link = trim((string) $item->link);
            $title = trim((string) $item->title);
            if ($link === '' || $title === '') {
                continue;
            }

            $categories = array_map('strval', iterator_to_array($item->category));
            $lab = $item->children('https://labradorcms.com/rss');
            if (isset($lab->kicker) && trim((string) $lab->kicker) !== '') {
                $categories[] = trim((string) $lab->kicker);
            }

            $items[] = [
                'title' => $title,
                'url' => $link,
                'description' => trim((string) $item->description),
                'published_at' => trim((string) $item->pubDate) ?: null,
                'labels' => array_values(array_filter($categories)),
                'is_subscription' => false,
            ];
        }

        return $items;
    }
}
