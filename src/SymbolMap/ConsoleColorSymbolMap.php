<?php


namespace roxblnfk\Soco\SymbolMap;

/**
 * Colors: black, red, green, yellow, blue, magenta, cyan, white, default
 */
class ConsoleColorSymbolMap extends AbstractSymbolMap
{
    protected static string $undefinedSymbol = '<bg=red>?</>';
    protected static string $outBorderSymbol = '<bg=cyan> </>';
    protected static array $symbolMap = [
        '<fg=cyan>@</>'   => [2, 0],
        '<fg=red>@</>'    => [3, 0],
        ' '               => [2],
        '<fg=yellow>×</>' => [3],
        '<fg=white>#</>'  => [4],
        '<fg=red>O</>'    => [2, 5],
        '<fg=green>Θ</>'  => [3, 5],
        '<bg=cyan>F</>'   => [1],
    ];
}
