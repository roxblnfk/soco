<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Control;

use roxblnfk\Soco\Gameplay\Action\DebugAction;
use roxblnfk\Soco\Gameplay\Action\MoveDownAction;
use roxblnfk\Soco\Gameplay\Action\MoveLeftAction;
use roxblnfk\Soco\Gameplay\Action\MoveRightAction;
use roxblnfk\Soco\Gameplay\Action\MoveUpAction;
use roxblnfk\Soco\Gameplay\Action\RestartLevelAction;
use roxblnfk\Soco\Gameplay\Action\RollbackAction;
use roxblnfk\Soco\Gameplay\Command\Command;

class WasdControl extends AbstractControl
{
    public function sendSignal($signal)
    {
        $signal = is_array($signal) ? implode('', $signal) : (string)$signal;
        $results = $this->strToCommands($signal);
        foreach ($results as $command) {
            $this->commandQueue->push($command);
        }
    }

    /**
     * @param string $str
     * @return Command[]
     */
    protected function strToCommands(string $str): array
    {
        /** @var Command[] $actions */
        $actions = [];
        # combined commands

        # chars
        for ($len = strlen($str), $i = 0; $i < $len; ++$i) {
            $action = $this->charToCommand($str[$i]);
            if (!$action) {
                continue;
            }
            $actions[] = $action;
        }
        return $actions;
    }
    /**
     * @param string $char
     * @return Command|null
     */
    protected function charToCommand(string $char): ?Command
    {
        $chars = [
            'w' => MoveUpAction::class,
            'a' => MoveLeftAction::class,
            's' => MoveDownAction::class,
            'd' => MoveRightAction::class,
            ' ' => RollbackAction::class,
            'r' => RestartLevelAction::class,
            'q' => DebugAction::class,
        ];
        $char = strtolower($char);
        if (isset($chars[$char])) {
            return new Command($chars[$char]);
        }
        return null;
    }
}
