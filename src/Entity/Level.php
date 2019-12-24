<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Entity;

use DateTimeImmutable;
use roxblnfk\Soco\Level\TilesModel;

class Level implements \Serializable
{

    public ?int $id = null;
    public ?LevelGroup $levelGroup = null;
    public string $author;
    public string $levelName;
    public string $description = '';
    public int $rating = 0;
    public int $difficult = 0;
    public TilesModel $tiles;
    public DateTimeImmutable $createdAt;
    public bool $public = false;
    public bool $visible = false;
    public DateTimeImmutable $publishedAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->tiles = new TilesModel();
    }

    public function serialize(): string
    {
        return serialize([
            $this->id,
            $this->levelName,
            $this->description,
            $this->difficult,
            $this->rating,
            $this->tiles,
        ]
        );
    }

    public function unserialize($serialized): void
    {
        [
            $this->id,
            $this->levelName,
            $this->description,
            $this->difficult,
            $this->rating,
            $this->tiles,
        ] = unserialize($serialized, ['allowed_classes' => false]);
    }
}
