<?php
/**
 * Class representing a level's tile structure and operations.
 *
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Level;

use JsonSerializable;
use roxblnfk\Soco\Collection\LevelTileCollection;
use roxblnfk\Soco\Exception\NotFoundException;
use roxblnfk\Soco\SymbolMap\AbstractSymbolMap;
use roxblnfk\Soco\SymbolMap\StandardSymbolMap;
use SplQueue;

/**
 * Represents the level's tile structure and provides operations for manipulation.
 *
 * This class manages a 3D grid of tiles for a game level where:
 * - First coordinate (X) represents horizontal position
 * - Second coordinate (Y) represents vertical position
 * - Third coordinate (Z) represents the layer (0 for terrain, 1 for objects)
 *
 * The class supports serialization, creation from string representation,
 * and various transformation operations like rotation and flipping.
 */
class TilesModel implements JsonSerializable
{
    /**
     * 3D array of tiles organized by coordinates.
     * Structure: [x][y][z] where z=0 is terrain and z=1 is object
     *
     * @var TileObjectModel[][][]
     */
    public array $tiles;

    /**
     * Width of the level
     * @var int|null
     */
    public ?int $w;

    /**
     * Height of the level
     * @var int|null
     */
    public ?int $h;

    /**
     * Class name of the symbol map used for input
     * @var string
     */
    public $inputSymbolMap = StandardSymbolMap::class;

    /**
     * Class name of the symbol map used for output
     * @var class-string<AbstractSymbolMap>
     */
    public string $outputSymbolMap;

    /**
     * Constructor initializes the tile model with provided values
     *
     * @param array $values Initial values for the model
     */
    public function __construct($values = [])
    {
        $this->w = $values['w'] ?? null;
        $this->h = $values['h'] ?? null;
        $this->tiles = $values['tiles'] ?? [];
        $this->inputSymbolMap = $values['inputSymbolMap'] ?? StandardSymbolMap::class;
        $this->outputSymbolMap = $values['outputSymbolMap'] ?? StandardSymbolMap::class;
    }

    /**
     * Creates a TilesModel from a string representation of a level
     *
     * Parses a string into a tile structure, with each character mapped to a tile
     * through the symbol map. Validates that a player exists in the level.
     *
     * @param string      $string         String representation of the level
     * @param bool|array  $repair         Whether to repair and validate the level
     * @param class-string<AbstractSymbolMap>|null $inputSymbolMap Symbol map class to use for interpretation
     *
     * @return TilesModel New instance created from the string
     * @throws NotFoundException If player character not found in the level
     */
    public static function createFromString(
        string $string,
        array|bool $repair = true,
        ?string $inputSymbolMap = StandardSymbolMap::class
    ): TilesModel
    {
        $outputMap = $inputSymbolMap ?? StandardSymbolMap::class;
        # exists player
        $playerSymbols = array_keys($outputMap::getAllSymbolsWithId(0));
        $playerExists = false;
        foreach ($playerSymbols as $sym) {
            $bullPos = mb_strpos($string, $sym);
            if (false === $bullPos)
                continue;
            $playerExists = true;
        }

        $playerExists or throw new NotFoundException('@Player not found');

        $lines = explode("\n", trim($string));
        $tiles = [];
        $w = count($lines);
        $h = 0;
        foreach ($lines as $Y => $line) {
            $line = trim($line);
            $h = max(strlen($line), $h);
            for ($len = mb_strlen($line), $X = 0; $X < $len; ++$X) {
                $sym = mb_substr($line, $X, 1);
                $tiles[$X][$Y] = call_user_func([$outputMap, 'getTileFromSymbol'], $sym);
            }
        }

        $model = new static([
            'w'     => $w,
            'h'     => $h,
            'tiles' => $tiles,
        ]);
        if ($repair) {
            $model->repairAndCheck(is_array($repair) ? $repair : []);
        }

        return $model;
    }

    /**
     * Finds and returns all player objects in the level
     *
     * Searches through all tiles to find objects with ID=0 (players)
     * and returns them in a queue.
     *
     * @return SplQueue|int[]|TileObjectModel[] Queue of player objects with their coordinates set
     */
    public function findPlayers(): SplQueue
    {
        $players = new SplQueue();
        foreach ($this->tiles as $X => $lineX) {
            foreach ($lineX as $Y => $tileZ) {
                $tile = &$this->tiles[$X][$Y];
                if (isset($tile[1]) && $tile[1]->id === 0) {
                    $tile[1]->setXY($X, $Y);
                    $players->push($tile[1]);
                }
                unset($tile);
            }
        }
        $players->rewind();
        return $players;
    }

    /**
     * Converts the level to a string representation
     *
     * Creates a string where each tile is represented by a character
     * according to the output symbol map.
     *
     * @return string String representation of the level
     */
    public function levelToString(): string
    {
        /** @var string[] $lines */
        $lines = [];

        $x0 = min(array_keys($this->tiles));
        $y0 = $y1 = null;
        array_map(function ($colY) use (&$y0, &$y1) {
            if (!count($colY))
                return;
            $keys = array_keys($colY);
            $begin = reset($keys);
            $end = end($keys);
            if (is_null($y0) or $y0 > $begin) $y0 = $begin;
            if (is_null($y1) or $y1 < $end) $y1 = $end;
        }, $this->tiles);
        /**
         * Get keys of the row on the Y
         * @param $Y int
         * @return int[]
         */
        $fn_lineKeys = function ($Y) {
            $res = [];
            foreach ($this->tiles as $x => $colY) if (key_exists($Y, $colY)) $res[] = $x;
            return $res;
        };
        /**
         * Checks if a position is surrounded by walls or earth tiles
         *
         * @param int $x X-coordinate
         * @param int $y Y-coordinate
         * @return bool True if surrounded by immovable tiles
         */
        $fn_wallOrEarth = function ($x, $y): bool {
            $coords = [[$x + 1, $y], [$x - 1, $y], [$x, $y + 1], [$x, $y - 1] ];
            foreach ($coords as [$X, $Y]) {
                if (!isset($this->tiles[$X][$Y][0]))
                    continue;
                if ($this->tiles[$X][$Y][0]->canMove !== 0)
                    return false;
            }
            return true;
        };
        //      ??###?###
        //      ??#-###-###
        //      ###-#?#---#
        //      #---###-###
        //      ##------#
        //      ?########
        $symWall      = call_user_func([$this->outputSymbolMap, 'getTileSymbolByIds'], [4]);
        $symUndef     = call_user_func([$this->outputSymbolMap, 'getUndefinedSymbol']);
        $symOutBorder = call_user_func([$this->outputSymbolMap, 'getOutBorderSymbol']);
        # prepare field: prefill
        for ($Y = $y0; $Y <= $y1; ++$Y) {
            $rowKeys = $fn_lineKeys($Y);
            // echo "keys: ".implode(',', $rowKeys)."\n";
            $x1 = max($rowKeys);
            $lines[$Y] = '';
            // $lines[$Y] = str_repeat('?', $x1 - $x0 + 1);
            for ($X = $x0; $X <= $x1; ++$X) {
                $tile = $this->tiles[$X][$Y] ?? [];
                if ($tile) {
                    $ids = [];
                    foreach ($tile as $Z => $el) $ids[] = $el->id;
                    $symbol = call_user_func([$this->outputSymbolMap, 'getTileSymbolByIds'], $ids) ?? $symUndef;
                } else {
                    $symbol = $fn_wallOrEarth($X, $Y) ? $symOutBorder : $symWall;
                }
                $lines[$Y] .= $symbol;
            }
        }
        return implode("\n", $lines) . "\n";
    }

    /**
     * Repairs and validates the level
     *
     * Performs various checks and repairs on the level to ensure it's valid:
     * - Removes objects that are locked (on walls)
     * - Adds ground under objects if needed
     * - Removes objects without ground
     * - Removes tiles that cannot be reached by the player
     * - Adds walls where needed
     *
     * @param array $repairs Configuration of which repairs to perform
     * @throws \Exception If no player is found in the level
     */
    public function repairAndCheck(array $repairs = []): array
    {
        # repairs:
        # Delete locked objects (objects on walls)
        $rep01 = $repairs['remove-locked-objects'] ?? true;
        # add ground under objects
        $rep02 = $repairs['add-ground-under-objects'] ?? true;
        # delete objects without ground
        $rep03 = $repairs['del-air-objects'] ?? false;
        # remove tiles on which the player can not get
        $rep04 = $repairs['del-unused-tiles'] ?? true;
        # add wall
        $rep05 = $repairs['add-walls'] ?? true;
        # check free balls in corners
        $check01 = $repairs['static-free-balls'] ?? true;

        # check count and pos of players
        /** @var int[][]*/
        $players = [];
        /** @var int[][] */
        $spots = [];
        /** @var int[][] */
        $balls = [];
        ksort($this->tiles);
        $keys = array_keys($this->tiles);
        /** @var int[] $box x:begin, y:begin, x:end, y:end */
        $box = $this->getBoxSize($this->tiles);
        foreach ($this->tiles as $X => $lineX) {
            ksort($lineX);
            foreach ($lineX as $Y => $tileZ) {
                $tile = &$this->tiles[$X][$Y];
                if (isset($tile[0])) {
                    # is unmovable
                    if ($rep01 and !$tile[0]->canMove && isset($tile[1]))
                        unset($tile[1]);
                    # is spot
                    if ($tile[0]->id === 3)
                        $spots[] = [$X, $Y];
                }
                if (isset($tile[1])) {
                    # add ground
                    if (!isset($tile[0])) {
                        if ($rep02) {
                            $tile[0] = LevelTileCollection::getTileFromId(2);
                            ksort($tile);
                        } elseif ($rep03) {
                            unset($tile[1]);
                        }
                    }
                    if (isset($tile[1])) {
                        # is player
                        if ($tile[1]->id === 0)
                            $players[] = [$X, $Y];
                        # is ball
                        if ($tile[1]->id === 5)
                            $balls[] = [$X, $Y];
                    }
                }
                unset($tile);
            }
        }

        # check player exists
        if (!count($players))
            throw new \Exception('@Player not found');

        if ($rep04 || $rep05) {
            # del unused space
            /** @var int[][] $matrix
             *  Values: null - unmatched
             *          0    - border
             *          1    - usable
             *          2    - potential border
             */
            $matrix = array_fill($box[0], $box[2] - $box[0] + 1, array_fill($box[1], $box[3] - $box[1] + 1, null));
            $fn_addLine = function ($end = true) use (&$box, &$matrix) {
                $Y = $end ? ++$box[3] : --$box[1];
                for ($X = $box[0]; $X <= $box[2]; ++$X) {
                    $matrix[$X][$Y] = null;
                    $this->tiles[$X][$Y] = [];
                    if (!$end) {
                        ksort($matrix[$X]);
                        ksort($this->tiles[$X]);
                    }
                }
            };
            $fn_addCol = function ($end = true) use (&$box, &$matrix) {
                $X = $end ? ++$box[2] : --$box[0];
                $matrix[$X] = array_fill($box[1], $box[3] - $box[1] + 1, null);
                $this->tiles[$X] = array_fill($box[1], $box[3] - $box[1] + 1, null);
                foreach ($this->tiles[$X] as $Y => &$tile) {
                    $tile = [];
                }
                if (!$end) {
                    ksort($matrix);
                    ksort($this->tiles);
                }
            };
            /**
             * Gets tiles around a specified position
             *
             * @param int $x X-coordinate
             * @param int $y Y-coordinate
             * @param null|int $direct Direction to exclude
             * @param bool $corners Whether to include corner tiles
             * @return int[][] Array of surrounding tile coordinates
             */
            $fn_getTilesAround = function ($x, $y, $direct = null, $corners = false) use (&$matrix) {
                $ret = [ [$x, $y - 1, 1], [$x, $y + 1, 0], [$x - 1, $y, 3], [$x + 1, $y, 2] ];
                if ($corners) {
                    $ret[] = [$x - 1, $y - 1, false];  # left up
                    $ret[] = [$x + 1, $y - 1, false];  # right up
                    $ret[] = [$x + 1, $y + 1, false];  # right down
                    $ret[] = [$x - 1, $y + 1, false];  # left down
                }
                if (is_int($direct)) {
                    unset($ret[$direct]);
                    if ($corners) switch ($direct) {
                        case 0: unset($ret[4], $ret[5]); break;
                        case 1: unset($ret[6], $ret[7]); break;
                        case 2: unset($ret[4], $ret[7]); break;
                        case 3: unset($ret[5], $ret[6]); break;
                    }
                    // return array_values($ret);
                }
                return $ret;
            };
            /**
             * Calculates the type of a tile and marks it in the matrix
             *
             * @param int $x X-coordinate
             * @param int $y Y-coordinate
             * @param null|false|int $direct Direction to exclude
             * @return bool|int[][] False or array of tiles to process next
             */
            $fn_calcTile = function ($x, $y, $direct = null) use (&$matrix, &$box, &$fn_getTilesAround, &$rep05, $fn_addLine, $fn_addCol) {
                # aboard
                if ($x < $box[0] || $x > $box[2] || $y < $box[1] || $y > $box[3]) {
                    # not need to add walls
                    if (!$rep05) #add wall
                        return false;
                    # add row on left
                    if ($x < $box[0])
                        $fn_addCol(false);
                    # add row on right
                    if ($x > $box[2])
                        $fn_addCol(true);
                    # add line up
                    if ($y < $box[1])
                        $fn_addLine(false);
                    # add line down
                    if ($y > $box[3])
                        $fn_addLine(true);
                    # mark as potential wall
                    $matrix[$x][$y] = 2;
                    return true;
                }
                # calculated
                if (!is_null($matrix[$x][$y]) && $matrix[$x][$y] !== 2) return true;
                # wall or no earth
                if (!isset($this->tiles[$x][$y][0])) {
                    $matrix[$x][$y] = 2;
                    return true;
                } elseif ($this->tiles[$x][$y][0]->canMove === 0) {
                    $matrix[$x][$y] = 0;
                    return true;
                }
                # mark as potential wall
                if ($direct === false) {
                    $matrix[$x][$y] = 2;
                    return false;
                }
                $matrix[$x][$y] = 1;
                return $fn_getTilesAround($x, $y, $direct, true);
            };
            foreach ($players as [$bulX, $bulY]) {
                if ($matrix[$bulX][$bulY] === 1)
                    continue;
                $matrix[$bulX][$bulY] = 1;
                $calcTiles = $fn_getTilesAround($bulX, $bulY, null, true);
                while (!is_null($xyd = array_pop($calcTiles))) {
                    # calc each tile around player
                    $res = $fn_calcTile(...$xyd);
                    if (is_array($res) && count($res)) {
                        $calcTiles = array_merge($calcTiles, $res);
                    }
                }
            }
            # do repairs
            foreach ($matrix as $X => $colY) {
                foreach ($colY as $Y => $t) {
                    # add walls
                    if ($rep05 and $t === 2) {
                        $this->tiles[$X][$Y] = StandardSymbolMap::getTileFromSymbol('#');
                        continue;
                    }
                    # remove tiles on which the player can not get
                    if ($rep04 and $t !== 1 && $t !== 0) {
                        unset($this->tiles[$X][$Y]);
                        // $this->tiles[$X][$Y] = [];
                    }
                }
            }

            // echo "\n\n";
            // $lines = [];
            // $symbols = ['#', '-', '~'];
            // foreach ($matrix as $X => $colY) {
            //     foreach ($colY as $Y => $tile) {
            //         if (!isset($lines[$Y]))
            //             $lines[$Y] = '';
            //
            //         if ($tile === null)
            //             $lines[$Y] .= '•';
            //         else
            //             $lines[$Y] .= $symbols[$tile];
            //     }
            // }
            // ksort($lines);
            // echo implode("\n", $lines);
            // echo "\n";
        }


        # get new width and height

        $this->w = $box[2] - $box[0];
        $this->h = $box[3] - $box[1];

        # check count of balls and spots
        # TODO

    }

    /**
     * Rotates the level in a specified direction
     *
     * @param int $direction Rotation direction:
     *        1 - to right (90° clockwise)
     *        2 - double (180°)
     *        -1 or 3 - to left (90° counterclockwise)
     */
    public function rotate($direction = 0): void
    {
        $direction = $direction % 4;
        if ($direction < 0)
            $direction = 4 + $direction;
        # get square
        $box = $this->getBoxSize($this->tiles);
        # double rotation
        if ($direction === 2) {
            $this->array_reverse($this->tiles, $box[0], $box[2]);
            foreach ($this->tiles as $X => &$colY) {
                $this->array_reverse($this->tiles[$X], $box[1], $box[3]);
            }
            return;
        }
        # to right
        if ($direction === 1) {
            $this->array_matrix_transpose($this->tiles);
            $this->array_reverse($this->tiles, $box[1], $box[3]);
        }
        # to left
        if ($direction === 3) {
            $this->array_matrix_transpose($this->tiles);
            foreach ($this->tiles as $X => &$colY) {
                $this->array_reverse($this->tiles[$X], $box[0], $box[2]);
            }
        }
    }

    /**
     * Flips the level horizontally or vertically
     *
     * @param bool $horizontal True for horizontal flip, false for vertical
     */
    public function flip($horizontal = true): void
    {
        # get square
        $box = $this->getBoxSize($this->tiles);
        if ($horizontal) {
            $this->array_reverse($this->tiles, $box[0], $box[2]);
        } else {
            foreach ($this->tiles as $X => &$colY) {
                $this->array_reverse($this->tiles[$X], $box[1], $box[3]);
            }
        }
    }

    /**
     * Normalizes the level's coordinates
     *
     * TODO: Implementation pending
     */
    public function normalizeLevel() {}

    /**
     * Serializes the model to a string
     *
     * {@inheritdoc}
     *
     * @return string Serialized representation
     */
    public function serialize(): string
    {
        return serialize([
            $this->w,
            $this->h,
            $this->serializeTiles(),
        ]);
    }

    /**
     * Unserializes the model from a string
     *
     * {@inheritdoc}
     *
     * @param string $serialized Serialized representation
     * @throws \Exception If there is an error during unserialization
     */
    public function unserialize(string $serialized): void
    {
        [
            $this->w,
            $this->h,
            $this->tiles,
        ] = unserialize($serialized, ['allowed_classes' => false]);

        $res = [];
        foreach ($this->tiles as $X => $colY) {
            foreach ($colY as $Y => $tileZ) {
                foreach ($tileZ as $Z => $elZ) {
                    $mdl = new TileObjectModel();
                    $mdl->unserialize($elZ);
                    $mdl->setXY($X, $Y);
                    $res[$X][$Y][$Z] = $mdl;
                }
            }
        }
        $this->tiles = $res;
    }

    /**
     * Sets the height of the level
     *
     * @param int $h New height value
     * @return TilesModel Self for method chaining
     */
    public function setH(int $h): TilesModel
    {
        $this->h = $h;
        return $this;
    }

    /**
     * Gets the bounding box of the level
     *
     * @param mixed[][] $array The array to calculate bounds for
     * @return int[] Bounding box as [x:begin, y:begin, x:end, y:end]
     */
    protected function getBoxSize(array &$array): array
    {
        if (!count($array))
            return [0, 0, 0, 0];
        $keys = array_keys($array);
        /** @var int[] $box x:begin, y:begin, x:end, y:end */
        $box = [min($keys), null, max($keys), null];
        foreach ($array as $X => $arrY) {
            # get min y:begin
            # todo: php7.3 array_key_first
            if (is_null($box[1]) || $box[1] > key($arrY))
                $box[1] = key($arrY);
            # get max y:end
            # todo: php7.3 array_key_last
            end($arrY);
            if (is_null($box[3]) || $box[3] < key($arrY))
                $box[3] = key($arrY);
        }
        return $box;
    }

    /**
     * Reverses an array within specified key bounds
     *
     * @param array $array Array to reverse
     * @param null|int $minKey Minimum key to include
     * @param null|int $maxKey Maximum key to include
     */
    protected function array_reverse(array &$array, $minKey = null, $maxKey = null): void
    {
        $ret = [];
        $keys = array_keys($array);
        if (is_null($minKey))
            $minKey = min($keys);
        if (is_null($maxKey))
            $maxKey = max($keys);
        //*
        $keys = \array_reverse($keys);
        foreach ($keys as $k) {
            $ret[is_numeric($k) ? $maxKey - $k + $minKey : $k] = &$array[$k];
            unset($v);
        }
        /*/
        foreach($array as $k => &$v) {
            $ret[$maxKey - $k + $minKey] = &$v;
            unset($v);
        }
        ksort($ret);
        // */
        $array = $ret;
    }

    /**
     * Transposes a 2D matrix (swaps rows and columns)
     *
     * @param mixed[][] $array Array to transpose
     */
    protected function array_matrix_transpose(array &$array): void
    {
        $keysY = [];
        $ret = [];
        # collect Y keys
        foreach ($array as $X => &$col)
            $keysY = array_merge($keysY, array_keys($col));
        $keysY = array_unique($keysY, SORT_NATURAL);
        foreach ($keysY as $newX) {
            $ret[$newX] = [];
            foreach ($array as $X => &$col) {
                if (key_exists($newX, $col))
                    $ret[$newX][$X] = &$array[$X][$newX];
            }
        }
        $array = $ret;
    }

    /**
     * Serializes tiles data for the serialize() method
     *
     * @return array Serialized tiles data
     */
    protected function serializeTiles(): array
    {
        $res = [];
        foreach ($this->tiles as $X => $colY) {
            foreach ($colY as $Y => $tileZ) {
                foreach ($tileZ as $Z => $elZ) {
                    $res[$X][$Y][$Z] = $elZ->serialize();
                }
            }
        }
        return $res;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'w' => $this->w,
            'h' => $this->h,
            'tiles' => $this->tiles,
        ];
    }
}
