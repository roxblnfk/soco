<?php


namespace roxblnfk\Soco\SymbolMap;

class ConsoleSymbolMap extends AbstractSymbolMap
{
    protected static string $undefinedSymbol = '?';
    protected static string $outBorderSymbol = ' ';
    protected static array $symbolMap = [
        '@' => [2, 0],
        'A' => [3, 0],
        ' ' => [2],
        '×' => [3],
        '#' => [4],
        'O' => [2, 5],
        'Θ' => [3, 5],
        'F' => [1],
    ];
}
