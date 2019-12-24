<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Gameplay\Action;

use roxblnfk\Soco\Helper\SeekableList;
use roxblnfk\Soco\Level\TileObjectModel;

abstract class AbstractMoveAction extends AbstractAction
{
    public int $modifyX = 0;
    public int $modifyY = 0;
    /** @var TileObjectModel[]|SeekableList */
    public $objectsToMove = [];
}
