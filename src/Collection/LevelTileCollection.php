<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Collection;

use roxblnfk\Soco\Exception\NotFoundException;
use roxblnfk\Soco\Level\TileObjectModel;
use roxblnfk\Soco\Repository\BaseObjectRepository;

class LevelTileCollection extends BaseObjectRepository {

    protected static $staticCollection = [
        # player
        0 => ['id' => 0, 'layer' => 1, 'canMove' => 1, 'power' => 15, 'texture' => ''],
        # void
        1 => ['id' => 1, 'layer' => 0, 'canMove' => 0, 'power' => 0, 'texture' => ''],
        # earth
        2 => ['id' => 2, 'layer' => 0, 'canMove' => 1, 'power' => 0, 'texture' => ''],
        # target
        3 => ['id' => 3, 'layer' => 0, 'canMove' => 1, 'power' => 0, 'texture' => ''],
        # wall
        4 => ['id' => 4, 'layer' => 0, 'canMove' => 0, 'power' => 0, 'texture' => ''],
        # ball
        5 => ['id' => 5, 'layer' => 1, 'canMove' => 1, 'power' => 10, 'texture' => ''],
        # block
        6 => ['id' => 6, 'layer' => 1, 'canMove' => 0, 'power' => 100, 'texture' => ''],
    ];

    /**
     * @param int $id
     * @param bool $asArray
     * @return TileObjectModel|array
     * @throws NotFoundException
     */
    public static function getTileFromId(int $id, bool $asArray = false) {
        if (!key_exists($id, static::$staticCollection))
            throw new NotFoundException("Undefined object id {$id}");
        return $asArray
            ? static::$staticCollection[$id]
            : new TileObjectModel(static::$staticCollection[$id]);
    }

    public function __construct() {
        parent::__construct(TileObjectModel::class);
        foreach (static::$staticCollection as $id => $item) {
            $this->collection->add(new TileObjectModel($item));
        }
    }
}
