<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace roxblnfk\Soco\Tests\Unit\Model;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use roxblnfk\Soco\Collection\LevelTileCollection;
use roxblnfk\Soco\Level\TilesModel;
use Throwable;

class LevelTilesModelTest extends TestCase
{

    private $repairs = [
        'remove-locked-objects' => false,
        'add-ground-under-objects' => false,
        'del-air-objects' => false,
        'del-unused-tiles' => false,
        'add-walls' => false,
    ];

    /**
     * @throws Exception
     */
    public function testCreateFromString()
    {
        # no player
        $this->assertException(Exception::class, function() {
            $string = "#####\n"
                    . "#--*#\n"
                    . "#-.-#\n"
                    . "#-$-#\n"
                    . "#####";
            TilesModel::createFromString($string, false);
        });

        # load simple level
        $model = TilesModel::createFromString(
              "#####\n"
            . "#--*#\n"
            . "#-.-#\n"
            . "#-$-#\n"
            . "#-@-#\n"
            . "#####", false
        );
        $compare = [
            [[4], [4],   [4], [4],   [4],   [4]],
            [[4], [2],   [2], [2],   [2],   [4]],
            [[4], [2],   [3], [2,5], [2,0], [4]],
            [[4], [3,5], [2], [2],   [2],   [4]],
            [[4], [4],   [4], [4],   [4],   [4]],
        ];
        $ids = $this->convertTilesObjectsToIds($model->tiles);
        $this->assertSame($compare, $ids);

        # load not squared level
        $model = TilesModel::createFromString(
              "-######\n"
            . "##-.-*#\n"
            . "#--$.-#\n"
            . "#-$-###\n"
            . "#-@-#-\n"
            . "#####--", false
        );
        $compare = [
            [[2], [4],   [4],   [4],   [4],   [4]],
            [[4], [4],   [2],   [2],   [2],   [4]],
            [[4], [2],   [2],   [2,5], [2,0], [4]],
            [[4], [3],   [2,5], [2],   [2],   [4]],
            [[4], [2],   [3],   [4],   [4],   [4]],
            [[4], [3,5], [2],   [4],   [2],   [2]],
            [[4], [4],   [4],   [4],       5=>[2]],
        ];
        $ids = $this->convertTilesObjectsToIds($model->tiles);
        $this->assertSame($compare, $ids);

        # load more players
        $model = TilesModel::createFromString(
              "-######\n"
            . "#-@@###\n"
            . "#*@-#-\n"
            . "#####--", false
        );
        $compare = [
            [[2], [4],   [4],   [4]],
            [[4], [2],   [3,5], [4]],
            [[4], [2,0], [2,0], [4]],
            [[4], [2,0], [2],   [4]],
            [[4], [4],   [4],   [4]],
            [[4], [4],   [2],   [2]],
            [[4], [4],       3=>[2]],
        ];
        $ids = $this->convertTilesObjectsToIds($model->tiles);
        $this->assertSame($compare, $ids);
    }
    /**
     * @throws Exception
     */
    public function testLevelToString()
    {
        # simple level
        $string = "#####\n"
                . "#--*#\n"
                . "#-.-#\n"
                . "#-$-#\n"
                . "#-@-#\n"
                . "#####\n";
        $model = TilesModel::createFromString($string, false);
        // echo "\n>>>>>>>>>>\n" . __FUNCTION__ . ":" . __LINE__ . "\n" . $model->levelToString() . "<<<<<<<<<<";
        $this->assertSame($string, $model->levelToString());

        # not squared level
        $string = "-######\n"
                . "##-.-*#\n"
                . "#--$.-#\n"
                . "#-$-###\n"
                . "#-@-#-\n"
                . "#####--";
        $model = TilesModel::createFromString($string, false);
        // echo "\n>>>>>>>>>>\n" . __FUNCTION__ . ":" . __LINE__ . "\n" . $model->levelToString() . "<<<<<<<<<<";
        $this->assertSame($string . "\n", $model->levelToString());

        # void tiles
        $string = "??###?#?#\n"
                . "??#-###-###\n"
                . "##?-#?#.--#\n"
                . "?---###$###\n"
                . "##--@---#\n"
                . "?########";
        $result = "--###-###\n"
                . "--#-###-###\n"
                . "###-#-#.--#\n"
                . "#---###$###\n"
                . "##--@---#\n"
                . "-########";
        $model = TilesModel::createFromString($string, false);
        unset(
            $model->tiles[0][0],
            // $model->tiles[1][0],
            $model->tiles[5][0],
            $model->tiles[7][0],
            $model->tiles[0][1],
            $model->tiles[1][1],
            // $model->tiles[2][2],
            $model->tiles[5][2],
            $model->tiles[0][3],
            $model->tiles[0][5]
        );
        $model->tiles[1][0] = [];
        $model->tiles[2][2] = [];
        // echo "\n>>>>>>>>>>\n" . __FUNCTION__ . ":" . __LINE__ . "\n" . $model->levelToString() . "<<<<<<<<<<";
        $this->assertSame($result . "\n", $model->levelToString());
    }
    /**
     * @throws Exception
     */
    public function testSerializeUnserialize() {
        # simple level
        $string = "#####\n"
                . "#--*#\n"
                . "#-.-#\n"
                . "#-$-#\n"
                . "#-@-#\n"
                . "#####\n";
        $model = TilesModel::createFromString($string, false);
        $serialized = $model->serialize();

        $model = new TilesModel();
        $model->unserialize($serialized);

        $this->assertSame($string, $model->levelToString());

    }
    /**
     * @throws Exception
     */
    public function testRepairAndCheck()
    {
        $string = "#####\n"
                . "#--*#\n"
                . "#-.-#\n"
                . "#-$-#\n"
                . "#-@-#\n"
                . "#####\n";
        $model = TilesModel::createFromString($string, false);


        # Delete locked objects (objects on walls)
        $model->tiles[0][0][1] = LevelTileCollection::getTileFromId(0);
        $model->tiles[1][0][1] = LevelTileCollection::getTileFromId(5);
        $model->repairAndCheck(array_merge($this->repairs, ['remove-locked-objects' => true]));
        $this->assertSame($string, $model->levelToString(), 'Task: Delete locked objects (objects on walls)');

        # Add ground under objects
        unset($model->tiles[2][3][0]);  # remove ground under ball
        unset($model->tiles[2][4][0]);  # remove ground under player
        $model->repairAndCheck(array_merge($this->repairs, ['add-ground-under-objects' => true]));
        $this->assertTrue(isset($model->tiles[2][3][0], $model->tiles[2][4][0]));
        $this->assertTrue($model->tiles[2][3][0]->id === 2);
        $this->assertTrue($model->tiles[2][4][0]->id === 2);
        $this->assertSame($string, $model->levelToString(), 'Task: Add ground under objects');

        # Delete objects without ground
        $model->tiles[1][1] = [1 => LevelTileCollection::getTileFromId(5)]; # add flied ball
        unset($model->tiles[3][1][0]); # remove spot under ball
        $result = "#####\n"
                . "##-##\n"
                . "#-.-#\n"
                . "#-$-#\n"
                . "#-@-#\n"
                . "#####\n";
        $model->repairAndCheck(array_merge($this->repairs, ['del-air-objects' => true]));
        // echo "\n>>>>>>>>>>\n" . __FUNCTION__ . ":" . __LINE__ . "\n" . $model->levelToString() . "<<<<<<<<<<";
        $this->assertSame($result, $model->levelToString(), 'Task: Delete objects without ground');


        # Add wall
        $string = "----\n"
                . "-$*-\n"
                . "-@.-\n"
                . "----\n";
        $result = "######\n"
                . "#----#\n"
                . "#-$*-#\n"
                . "#-@.-#\n"
                . "#----#\n"
                . "######\n";
        $model = TilesModel::createFromString($string, false);
        $model->repairAndCheck(array_merge($this->repairs, ['add-walls' => true]));
        $this->assertSame($result, $model->levelToString(), 'Task: Add walls');
        $string = "$*\n"
                . "@.\n";
        $result = "####\n"
                . "#$*#\n"
                . "#@.#\n"
                . "####\n";
        $model = TilesModel::createFromString($string, false);
        $model->repairAndCheck(array_merge($this->repairs, ['add-walls' => true]));
        $this->assertSame($result, $model->levelToString(), 'Task: Add walls');

        // echo "\n" . $model->levelToString() . "\n";
        // die;


        # Remove tiles on which the player can not get
        $string = "--#####\n"
                . "##-*#-#\n"
                . "#-@-###\n"
                . "#-$-###\n"
                . "#-.-#-#-\n"
                . "####-#.--\n"
                . "#@*##-#-#\n"
                . "###-##-\n";
        $result = "--###\n"
                . "##-*#\n"
                . "#-@-#\n"
                . "#-$-#\n"
                . "#-.-#\n"
                . "####\n"
                . "#@*#\n"
                . "###\n";
        $model = TilesModel::createFromString($string, false);
        $model->repairAndCheck(array_merge($this->repairs, ['del-unused-tiles' => true]));
        // echo "\n>>>>>>>>>>\n" . __FUNCTION__ . ":" . __LINE__ . "\n" . $model->levelToString() . "<<<<<<<<<<";
        $this->assertSame($result, $model->levelToString(), 'Task: Remove tiles on which the player can not get');
    }
    /**
     * @throws Exception
     */
    public function testArray_reverse() {
        $obj = new TilesModel();
        $fn = self::getMethod('array_reverse');

        $fn_doAction = function (...$args) use (&$fn, &$obj) {
            $result = &$args[0];
            $fn->invokeArgs($obj, $args);
            return $result;
        };

        $origin = ['foo', 'bar', 'baz', 'end'];
        $invert = ['end', 'baz', 'bar', 'foo'];
        $this->assertSame($invert, $fn_doAction($origin), 'array_reverse: simple');

        $origin = [
            -2 => 'foo',
            1  => 'bar',
            9  => 'baz',
            10 => 'end',
        ];
        $invert = [
            -2 => 'end',
            -1 => 'baz',
            7  => 'bar',
            10 => 'foo',
        ];
        $padded0 = [
            0 => 'end',
            1 => 'baz',
            9  => 'bar',
            12 => 'foo',
        ];
        $padded1 = [
            -4 => 'end',
            -3 => 'baz',
            5  => 'bar',
            8  => 'foo',
        ];
        $this->assertSame($invert, $fn_doAction($origin), 'array_reverse: different keys');
        $this->assertSame($invert, $fn_doAction($origin, -3, 11), 'array_reverse: different keys');
        $this->assertSame($padded0, $fn_doAction($origin, -2, 12), 'array_reverse: padding right');
        $this->assertSame($padded1, $fn_doAction($origin, -4, 10), 'array_reverse: padding left');
    }
    /**
     * @throws Exception
     */
    public function testArray_matrix_transpose() {
        $obj = new TilesModel();
        $fn = self::getMethod('array_matrix_transpose');

        $fn_doAction = function (...$args) use (&$fn, &$obj) {
            $result = &$args[0];
            $fn->invokeArgs($obj, $args);
            return $result;
        };

        $origin = [
            [1,  2,  3,  4,  5],
            [6,  7,  8,  9,  0],
            [10, 17, 15, 14, 11],
            [18, 19, 20, 21, 22],
        ];
        $result = [
            [1, 6, 10, 18],
            [2, 7, 17, 19],
            [3, 8, 15, 20],
            [4, 9, 14, 21],
            [5, 0, 11, 22],
        ];
        $this->assertSame($result, $fn_doAction($origin), 'array_matrix_transpose: simple');

        # not squared and string keys
        $origin = [
            [1,  2,  3,   4, 5        ],
            [6,  7,          4 => 9, 0],
            [10, 17, 15, 14, 11       ],
            [18, 19, 20,     's' => 21],
        ];
        $result = [
            [1,   6,      10, 18],
            [2,   7,      17, 19],
            [3,      2 => 15, 20],
            [4,      2 => 14],
            [5,   9,      11,],
            [1 => 0],
            's' => [3 => 21]
        ];
        $this->assertSame($result, $fn_doAction($origin), 'array_matrix_transpose: simple');
    }
    /**
     * @throws Exception
     */
    public function testFlip()
    {
        # load not squared level
        $origin = ''
            . "#####\n"
            . "#--*###\n"
            . "##-.--#\n"
            . "-##$--#\n"
            . "--#@-##\n"
            . "--####\n";
        $model = TilesModel::createFromString($origin, [
            'del-unused-tiles' => true,
            'add-walls'        => true,
        ]);
        $fig1 = ''
            . "--#####\n"
            . "###*--#\n"
            . "#--.-##\n"
            . "#--$##\n"
            . "##-@#\n"
            . "-####\n";
        $fig2 = ''
            . "--####\n"
            . "--#@-##\n"
            . "-##$--#\n"
            . "##-.--#\n"
            . "#--*###\n"
            . "#####\n"
        ;
        $fig3 =   ''
            . "-####\n"
            . "##-@#\n"
            . "#--$##\n"
            . "#--.-##\n"
            . "###*--#\n"
            . "--#####\n";
        # Flip horizontal
        $model->flip(true);
        $this->assertSame($fig1, $model->levelToString(), 'Flip horizontal');
        # Flip vertical
        $model->flip(false);
        $this->assertSame($fig3, $model->levelToString(), 'Flip vertical after horizontal');

        $model = TilesModel::createFromString($origin, [
            'del-unused-tiles' => true,
            'add-walls'        => true,
        ]);
        $model->flip(false);
        $this->assertSame($fig2, $model->levelToString(), 'Flip vertical');
    }
    /**
     * @throws Exception
     */
    public function testRotate()
    {
        # load simple level
        $origin = ''
            . "#####\n"
            . "#--*#\n"
            . "#-.-#\n"
            . "#-$-#\n"
            . "#-@-#\n"
            . "#####\n";
        $model = TilesModel::createFromString($origin, false);
        $fig1 = ''
            . "######\n"
            . "#----#\n"
            . "#@$.-#\n"
            . "#---*#\n"
            . "######\n";
        $fig2 = ''
            . "#####\n"
            . "#-@-#\n"
            . "#-$-#\n"
            . "#-.-#\n"
            . "#*--#\n"
            . "#####\n";
        $fig3 = ''
            . "######\n"
            . "#*---#\n"
            . "#-.$@#\n"
            . "#----#\n"
            . "######\n";
        $originArr = [
            [[4], [4],   [4], [4],   [4],   [4]],
            [[4], [2],   [2], [2],   [2],   [4]],
            [[4], [2],   [3], [2,5], [2,0], [4]],
            [[4], [3,5], [2], [2],   [2],   [4]],
            [[4], [4],   [4], [4],   [4],   [4]],
        ];
        $rotated = [
            [[4], [4], [4],   [4],   [4]],
            [[4], [2], [2,0], [2],   [4]],
            [[4], [2], [2,5], [2],   [4]],
            [[4], [2], [3],   [2],   [4]],
            [[4], [2], [2],   [3,5], [4]],
            [[4], [4], [4],   [4],   [4]],
        ];
        $ids = $this->convertTilesObjectsToIds($model->tiles);
        $this->assertSame($originArr, $ids, 'Rotate level to right: ids compare - before');
        # Rotate to right
        $model->rotate(1);
        $ids = $this->convertTilesObjectsToIds($model->tiles);
        $this->assertSame($rotated, $ids, 'Rotate level to right: ids compare - after');
        ## GRAPHICAL
        $this->assertSame($fig1, $model->levelToString(), 'Rotate level to right: first graphical');
        $model->rotate(-3);
        $this->assertSame($fig2, $model->levelToString(), 'Rotate level to right: double right');
        $model->rotate(5);
        $this->assertSame($fig3, $model->levelToString(), 'Rotate level to right: triple right');
        $model->rotate(1);
        $this->assertSame($origin, $model->levelToString(), 'Rotate level to right 4x: to origin');
        # Double rotation
        $model->rotate(2);
        $this->assertSame($fig2, $model->levelToString(), 'Rotate 180: double rotation');
        $model->rotate(2);
        $this->assertSame($origin, $model->levelToString(), 'Rotate 180 + 180: to origin');
        # Rotate to left
        $model->rotate(3);
        $this->assertSame($fig3, $model->levelToString(), 'Rotate level to left: 1x');
        $model->rotate(-1);
        $this->assertSame($fig2, $model->levelToString(), 'Rotate level to left: 2x');
        $model->rotate(3);
        $this->assertSame($fig1, $model->levelToString(), 'Rotate level to left: 3x');
        $model->rotate(7);
        $this->assertSame($origin, $model->levelToString(), 'Rotate level to left: 4x');

        # load not squared level
        $origin = ''
            . "#####\n"
            . "#--*###\n"
            . "##-.--#\n"
            . "-##$--#\n"
            . "--#@-##\n"
            . "--####\n";
        $model = TilesModel::createFromString($origin, [
            'del-unused-tiles' => true,
            'add-walls'        => true,
        ]);
        $fig1 =  ''
            . "---###\n"
            . "--##-#\n"
            . "###--#\n"
            . "#@$.*#\n"
            . "#---##\n"
            . "##--#\n"
            . "-####\n";
        $fig2 =   ''
            . "-####\n"
            . "##-@#\n"
            . "#--$##\n"
            . "#--.-##\n"
            . "###*--#\n"
            . "--#####\n";
        $fig3   = ''
            . "-####\n"
            . "-#--##\n"
            . "##---#\n"
            . "#*.$@#\n"
            . "#--###\n"
            . "#-##\n"
            . "###\n";
        # Rotate to right
        $model->rotate(1);
        $this->assertSame($fig1, $model->levelToString(), 'Rotate level to right');
        # Double rotation
        $model->rotate(2);
        $this->assertSame($fig3, $model->levelToString(), 'Rotate 180: double rotation');
        # Rotate to left
        $model->rotate(3);
        $this->assertSame($fig2, $model->levelToString(), 'Rotate level to left');
    }
    /**
     * @throws Exception
     */
    public function testGetBoxSize() {
        $obj = new TilesModel();
        $fn = self::getMethod('getBoxSize');

        $origin = [
            [1, 2, 3, 4, 5, 6, 7, 8],
            [1, 2, 3, 4, 5, 6, 7, 8],
            [1, 2, 3, 4, 5, 6, 7, 8],
        ];
        $result = [0, 0, 2, 7];
        $this->assertSame($result, $fn->invokeArgs($obj, [&$origin]), 'GetBoxSize: simple');
        $origin = [
            3  => [6  => 1],
            10 => [-3  => 2],
            20 => [10 => 3],
        ];
        $result = [3, -3, 20, 10];
        $this->assertSame($result, $fn->invokeArgs($obj, [&$origin]), 'GetBoxSize: not simple');
    }



    private function convertTilesObjectsToIds(array $tiles, $print = false) {
        if ($print)
            echo "\n\n";
        $lines = [];
        foreach ($tiles as $X => $rowY) {
            foreach ($rowY as $Y => $tile) {
                $ids = [];
                foreach ($tile as $Z => $el) {
                    $ids[] = $el->id;
                }
                $tiles[$X][$Y] = $ids;

                if (!isset($lines[$Y]))
                    $lines[$Y] = '';
                $lines[$Y] .= ' ' . str_pad(implode(',', $ids), 3);
            }
        }
        if ($print) {
            ksort($lines);
            echo implode("\n", $lines);
        }
        return $tiles;
    }
    /**
     * Asserts that the given callback throws the given exception.
     *
     * @param string $expectClass The name of the expected exception class
     * @param callable $callback A callback which should throw the exception
     */
    protected function assertException(string $expectClass, callable $callback)
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            $this->assertInstanceOf($expectClass, $exception, 'An invalid exception was thrown');
            return;
        }

        $this->fail('No exception was thrown');
    }
    /**
     * @param $name
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected static function getMethod($name) {
        $class = new ReflectionClass(TilesModel::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}
