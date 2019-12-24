<?php

namespace roxblnfk\Soco\SymbolMap;

use roxblnfk\Soco\Collection\LevelTileCollection;
use roxblnfk\Soco\Exception\NotFoundException;
use roxblnfk\Soco\Level\TileObjectModel;

abstract class AbstractSymbolMap
{
    protected static string $undefinedSymbol = '?';
    protected static string $outBorderSymbol = '-';
    /** @var int[][] */
    protected static array $symbolMap;

    /**
     * @param string $symbol
     * @return TileObjectModel[]
     * @throws NotFoundException
     */
    public static function getTileFromSymbol(string $symbol): array
    {
        if (!key_exists($symbol, static::$symbolMap)) {
            if ($symbol === static::$outBorderSymbol) {
                $assoc = [2];
            } else {
                throw new NotFoundException("Undefined symbol {$symbol}");
            }
        } else {
            $assoc = (array)static::$symbolMap[$symbol];
        }
        $ret = [];
        foreach ($assoc as $key) {
            $el = LevelTileCollection::getTileFromId($key);
            $z = $el->layer;
            $ret[$z] = $el;
        }
        ksort($ret);
        return $ret;
    }

    public static function getTileSymbolByIds(array $ids): ?string
    {
        $key = array_search($ids, static::$symbolMap, true);
        return false === $key ? null : $key;
    }

    /**
     * @param int $id
     * @return int[][] Array with elements : Symbol=>Ids
     */
    public static function getAllSymbolsWithId(int $id): array
    {
        $ret = [];
        foreach (static::$symbolMap as $sym => $ids) {
            if (in_array($id, $ids, true))
                $ret[$sym] = $ids;
        }
        return $ret;
    }

    public static function getUndefinedSymbol(): string
    {
        return static::$undefinedSymbol;
    }

    public static function getOutBorderSymbol(): string
    {
        return static::$outBorderSymbol;
    }

}
