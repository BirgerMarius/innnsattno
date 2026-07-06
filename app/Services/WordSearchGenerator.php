<?php

namespace App\Services;

class WordSearchGenerator
{
    private int $size = 15;

    public function generate(): array
    {
        $words = [
            'BJORN',
            'ELG',
            'REV',
            'ULV',
            'HARE',
            'UGLE',
            'EKORN',
            'MUS',
            'SEL',
            'ORN'
        ];

        $grid = [];

        for ($row = 0; $row < $this->size; $row++) {
            for ($col = 0; $col < $this->size; $col++) {
                $grid[$row][$col] = chr(rand(65, 90));
            }
        }

        return [
            'grid' => $grid,
            'words' => $words
        ];
    }
}