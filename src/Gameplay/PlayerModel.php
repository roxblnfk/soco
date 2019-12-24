<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Gameplay;

use roxblnfk\Soco\Level\TileObjectModel;
use SplQueue;

class PlayerModel
{
    /** @var SplQueue|TileObjectModel[] */
    protected $myCars;

    public function setCars(SplQueue $cars) {
        $this->myCars = $cars;
    }

    public function getCurrentCar(): ?TileObjectModel
    {
        return $this->myCars->current();
    }
}
