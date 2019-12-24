<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Repository\FS;

use roxblnfk\Soco\Entity\Level;
use roxblnfk\Soco\Entity\LevelGroup;
use roxblnfk\Soco\Exception\NotFoundException;
use roxblnfk\Soco\Level\TilesModel;
use roxblnfk\Soco\Repository\LevelGroupRepositoryInterface;
use roxblnfk\Soco\Repository\LevelRepositoryInterface;

class LevelRepository extends BaseObjectRepository implements LevelRepositoryInterface
{
    public function getRandom(?LevelGroup $group = null): Level
    {
        if ($group !== null) {
            # get random level group
            $level = $group->levels->getRandom();
            if ($level === null) {
                throw new NotFoundException('Level not found (group is empty)');
            }
            return $level;
        } elseif ($this->collection->count() > 0) {
            return parent::getRandom();
        }

        $string = "-#####\n"
            . "-#--*#\n"
            . "-#-.-#\n"
            . "##-$-#\n"
            . "#-@-##\n"
            . "#####\n";
        $tiles = TilesModel::createFromString($string, false);
        $level = new Level();
        $level->id = 1;
        $level->levelName = 'test';
        $level->author = 'author';
        $level->public = true;
        $level->publishedAt = new \DateTimeImmutable();
        $level->tiles = $tiles;
        return $level;
    }
}
