<?php
/**
 * @author roxblnfk
 * Date: 20.10.2018
 */

namespace roxblnfk\Soco\Level;

use JsonSerializable;
use roxblnfk\Soco\Collection\LevelTileCollection;

class TileObjectModel implements JsonSerializable
{
    /** Tile ID */
    public int $id;
    /** 0 - ground; 1 - object */
    public int $layer = 0;
    /** 0 - can't be moved */
    public int $canMove = 1;
    /** Power of counteraction */
    public int $power = 0;
    public string $texture = '';
    /** @var int[] */
    public array $xy = [null, null];

    /** @var mixed[] */
    public $extras = [];

    public function __construct(array $values = [])
    {
        if ($values)
            $this->fillFields($values);
    }

    public function fillFields($values)
    {
        if (isset($values['id']))
            $this->id      = $values['id'];
        if (isset($values['layer']))
            $this->layer   = $values['layer'];
        if (isset($values['canMove']))
            $this->canMove = $values['canMove'];
        if (isset($values['power']))
            $this->power   = $values['power'];
        if (isset($values['texture']))
            $this->texture = $values['texture'];
    }

    public function toArray(): array
    {
        return [
            'id'      => $this->id,
            'layer'   => $this->layer,
            'canMove' => $this->canMove,
            'power'   => $this->power,
            'texture' => $this->texture,
        ];
    }

    public function setXY(int $x, int $y)
    {
        return $this->xy = [$x, $y];
    }

    protected function serializeExtras()
    {
        return null;
    }

    public function serialize(): string
    {
        return serialize([$this->id]);
        // return serialize([$this->id, $this->serializeExtras()]);
    }

    public function unserialize(string $serialized): void
    {
        [$id] = unserialize($serialized, ['allowed_classes' => false]);
        // [$id, $extras] = unserialize($serialized, ['allowed_classes' => false]);
        $values = LevelTileCollection::getTileFromId($id, true);
        $this->fillFields($values);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
