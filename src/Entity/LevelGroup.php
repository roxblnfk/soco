<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Entity;

use roxblnfk\Soco\Repository\LevelRepositoryInterface;

class LevelGroup
{
    public ?int $id = null;
    public string $groupName;
    public string $description;
    /**
     * @var LevelRepositoryInterface|Level[]
     */
    public $levels;

    public function __serialize(): array
    {
        return [$this->id, $this->groupName, $this->description];
    }
    public function __unserialize($serialized): void
    {
        [$this->id, $this->groupName, $this->description] = unserialize($serialized, ['allowed_classes' => false]);
    }
}
