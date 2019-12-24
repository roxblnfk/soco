<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco;

use roxblnfk\Soco\Collection\ActionHistory;
use roxblnfk\Soco\Collection\CommandQueue;
use roxblnfk\Soco\Console\Helper\Screen;
use roxblnfk\Soco\Gameplay\Rule\AbstractRule;
use roxblnfk\Soco\Level\TilesModel;

class GameVisualizer
{
    public string $title = '';
    public string $state = '';

    protected ActiveGameModel $activeGame;
    protected TilesModel $levelTiles;
    protected string $levelTilesOrigin;
    protected CommandQueue $commandsQueue;
    protected ActionHistory $actionHistory;
    protected AbstractRule $gameRules;
    protected Screen $screen;


    public function __construct(ActiveGameModel $activeGame, Screen $screen)
    {
        $this->activeGame = $activeGame;
        $this->commandsQueue = $activeGame->getCommandsQueue();
        $this->levelTiles = $activeGame->getLevelTiles();
        $this->actionHistory = $activeGame->getActionHistory();
        $this->gameRules = $activeGame->getGameRules();

        $this->screen = $screen;
    }

    public function update()
    {
        $frame = &$this->screen->frame;
        $array = [];
        strlen($this->title) and $array[] = $this->title;
        $state = $this->state ?: $this->gameRules->stateString;
        $array[] = $state;

        $frame = array_merge($array, explode("\n", $this->levelTiles->levelToString()));
        $finished = $this->activeGame->isFinished();
        if (is_bool($finished)) {
            $frame[] = $finished ? '  > VICTORY! <  ' : '  > DEFEAT! <  ';
        }
    }
}
