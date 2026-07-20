<?php
namespace App\Services\News\Parsers;
use App\NewsSource;
interface ParserInterface { public function supports(NewsSource $source): bool; public function parse(string $content, NewsSource $source): array; }
