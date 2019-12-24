<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Collection;

use roxblnfk\Soco\Gameplay\Command\Command;

/**
 * @method Command current()
 * @method Command dequeue()
 * @method Command bottom()
 * @method Command shift()
 * @method Command top()
 */
class CommandQueue extends \SplQueue
{
}
