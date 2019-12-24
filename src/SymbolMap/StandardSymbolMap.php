<?php


namespace roxblnfk\Soco\SymbolMap;

class StandardSymbolMap extends AbstractSymbolMap
{
    protected static array $symbolMap = [
        '@' => [2, 0],
        '+' => [3, 0],
        '-' => [2],
        '.' => [3],
        '#' => [4],
        '$' => [2, 5],
        '*' => [3, 5],
        '?' => [1],
    ];
}
