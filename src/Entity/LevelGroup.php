<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Entity;

use Collections\Vector;
use roxblnfk\Soco\Repository\LevelRepositoryInterface;

class LevelGroup implements \Serializable
{
    public ?int $id = null;
    public string $groupName;
    public string $description;
    /**
     * @var LevelRepositoryInterface|Level[]
     */
    public $levels;

    public function serialize(): string
    {
        return serialize([$this->id, $this->groupName, $this->description]);
    }
    public function unserialize($serialized): void
    {
        [$this->id, $this->groupName, $this->description] = unserialize($serialized, ['allowed_classes' => false]);
    }
}
