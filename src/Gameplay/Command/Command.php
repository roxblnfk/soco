<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Gameplay\Command;

class Command
{
    public string $action;

    public function __construct(string $action)
    {
        $this->action = $action;
    }
}
