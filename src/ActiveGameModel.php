<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco;

use roxblnfk\Soco\Gameplay\Action\AbstractMoveAction;
use roxblnfk\Soco\Gameplay\Action\DebugAction;
use roxblnfk\Soco\Gameplay\Action\RestartLevelAction;
use roxblnfk\Soco\Gameplay\Action\RollbackAction;
use roxblnfk\Soco\Gameplay\Action\AbstractAction;
use roxblnfk\Soco\Gameplay\Event\AbstractEvent;
use roxblnfk\Soco\Gameplay\Event\DefeatEvent;
use roxblnfk\Soco\Gameplay\Event\StopActionEvent;
use roxblnfk\Soco\Gameplay\Event\VictoryEvent;
use roxblnfk\Soco\Collection\ActionHistory;
use roxblnfk\Soco\Collection\CommandQueue;
use roxblnfk\Soco\Gameplay\Command\Command;
use roxblnfk\Soco\Gameplay\PlayerModel;
use roxblnfk\Soco\Helper\SeekableList;
use roxblnfk\Soco\Gameplay\Rule\AbstractRule;
use roxblnfk\Soco\Gameplay\Rule\ClassicRule;
use roxblnfk\Soco\Level\TileObjectModel;
use roxblnfk\Soco\Level\TilesModel;

class ActiveGameModel
{
    protected TilesModel $levelTiles;
    protected string $levelTilesOrigin;
    protected CommandQueue $commandsQueue;
    protected ActionHistory $actionHistory;
    protected AbstractRule $gameRules;
    protected PlayerModel $player;
    /** True if victory, false if defeat */
    protected ?bool $finished = null;

    public $debugValue;


    public function __construct(
        ?CommandQueue $commandsQueue,
        ?ActionHistory $actionHistory,
        ?TilesModel $tilesModel,
        ?AbstractRule $rule
    )
    {
        $this->commandsQueue = $commandsQueue ?? new CommandQueue();
        $this->actionHistory = $actionHistory ?? new ActionHistory();
        $this->levelTiles = $tilesModel ?? new TilesModel();
        $this->levelTilesOrigin = $this->levelTiles->serialize();
        $this->player = new PlayerModel();
        $this->gameRules = $rule ?? new ClassicRule($this->levelTiles, $this->actionHistory);

        $this->restartLevel();
    }
    protected function startLevel()
    {
        $this->finished = null;
        $this->actionHistory->clean();
        $this->player->setCars($this->levelTiles->findPlayers());
        $this->gameRules->onLevelStart();
    }

    public function process()
    {
        while (!$this->commandsQueue->isEmpty()) {
            # get command
            $command = $this->commandsQueue->dequeue();

            # convert to action
            if (!$action = $this->execCommand($command))
                continue;

            if ($event = $this->gameRules->beforeAction($action)) {
                if ($event instanceof StopActionEvent)
                    continue;
                $this->processEvent($event);
            }

            # run action
            if ($storeAction = $this->applyAction($action)) {
                # add to history
                if ($this->finished === null) {
                    $this->actionHistory->sendSplice($storeAction);
                }
            }
            if ($event = $this->gameRules->afterAction($storeAction ?? $action)) {
                $this->processEvent($event);
            }
        }
    }
    protected function processEvent(AbstractEvent $event)
    {
        if ($event instanceof VictoryEvent)
            $this->finished = true;
        if ($event instanceof DefeatEvent)
            $this->finished = false;
    }

    /**
     * True if victory, false if defeat, null if no result
     */
    public function isFinished(): ?bool
    {
        return $this->finished;
    }

    public function getActionHistory(): ActionHistory
    {
        return $this->actionHistory;
    }

    public function getGameRules(): AbstractRule
    {
        return $this->gameRules;
    }

    public function getLevelTiles(): TilesModel
    {
        return $this->levelTiles;
    }

    public function getCommandsQueue(): CommandQueue
    {
        return $this->commandsQueue;
    }

    /**
     * Convert Command into Action if possible
     * @param Command $command
     * @return AbstractAction|null
     */
    protected function execCommand(Command $command): ?AbstractAction
    {
        if (in_array($command->action, [DebugAction::class, RollbackAction::class, RestartLevelAction::class], true)) {
            return  new $command->action();
        }
        # some movie action
        if (is_a($command->action, AbstractMoveAction::class, true)) {
            return $this->wantToMove(new $command->action());
        }
        return null;
    }
    /**
     * Check for can to move
     */
    protected function wantToMove(AbstractMoveAction $action): ?AbstractMoveAction {
        # get current mover
        $carTile = $this->player->getCurrentCar();
        [$X, $Y] = $carTile->xy;
        # check can move
        # X and Y modifiers
        $mdfX = $action->modifyX;
        $mdfY = $action->modifyY;
        $tiles = &$this->levelTiles->tiles;
        $objectsToMove = new SeekableList();
        $objectsToMove->push([$X, $Y, $carTile]);
        $power = $carTile->power;
        $XX = $X; $YY = $Y;
        $ok = false;
        do {
            $XX += $mdfX;
            $YY += $mdfY;
            /** @var TileObjectModel[] $tile */
            $tile = $tiles[$XX][$YY] ?? false;
            # can not moved
            if (!(isset($tile[0]) && $tile[0]->canMove and !isset($tile[1]) || $tile[1]->canMove)) break;
            $power -= $tile[0]->power;
            if (!isset($tile[1])) {
                $ok = $power >= 0;
                break;
            }
            $power -= $tile[1]->power;
            # insert objects into action
            $objectsToMove->push([$XX, $YY, $tile[1]]);
        } while ($power >= 0);
        if (!$ok)
            return null;
        $objectsToMove->rewind();
        $action->objectsToMove = $objectsToMove;
        return $action;
    }

    public function debugAction(): ?AbstractAction
    {
        # paste debug code here
        if (isset($this->debugValue)) {
            var_dump($this->debugValue);
            die;
        }
        return null;
    }

    protected function applyAction(AbstractAction $action): ?AbstractAction
    {
        if ($action instanceof DebugAction) {
            return $this->debugAction();
        }
        if ($action instanceof RestartLevelAction) {
            $this->restartLevel();
            return null;
        }

        if (is_bool($this->finished)) {
            return null;
        }

        if ($action instanceof RollbackAction) {
            $this->rollbackAction();
            return null;
        }
        # some move action
        if ($action instanceof AbstractMoveAction) {
            # X and Y modifiers
            $mdfX = $action->modifyX;
            $mdfY = $action->modifyY;
            $tiles = &$this->levelTiles->tiles;
            $action->objectsToMove->wind();
            while ($action->objectsToMove->valid()) {
                /** @var TileObjectModel $tile */
                [$x, $y, $tile] = $action->objectsToMove->current();
                $tiles[$x + $mdfX][$y + $mdfY][$tile->layer] = $tile;
                unset($tiles[$x][$y][$tile->layer]);
                $tile->setXY($x + $mdfX, $y + $mdfY);
                $action->objectsToMove->prev();
            }
        }
        return $action;
    }
    protected function restartLevel(): void
    {
        $this->levelTiles->unserialize($this->levelTilesOrigin);
        $this->startLevel();
    }
    protected function rollbackAction()
    {
        if (!$this->actionHistory->valid())
            return;
        $action = $this->actionHistory->current();

        # some movie action
        if ($action instanceof AbstractMoveAction) {
            # X and Y modifiers
            $mdfX = $action->modifyX;
            $mdfY = $action->modifyY;
            $tiles = &$this->levelTiles->tiles;
            $action->objectsToMove->rewind();
            while ($action->objectsToMove->valid()) {
                /** @var TileObjectModel $tile */
                [$x, $y, $tile] = $action->objectsToMove->current();
                $tiles[$x][$y][$tile->layer] = $tile;
                unset($tiles[$x + $mdfX][$y + $mdfY][$tile->layer]);
                $tile->setXY($x, $y);
                $action->objectsToMove->next();
            }
            $this->actionHistory->prev();
        }
    }
}
