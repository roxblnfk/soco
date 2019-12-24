<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Control;

use roxblnfk\Soco\Collection\CommandQueue;

abstract class AbstractControl
{
    protected CommandQueue $commandQueue;

    public function __construct(CommandQueue $commandQueue)
    {
        $this->commandQueue = $commandQueue;
    }

    /**
     * @param mixed $signal
     */
    abstract public function sendSignal($signal);
}
