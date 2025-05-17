<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Boson;

use Boson\WebView\WebView;
use roxblnfk\Soco\ActiveGameModel;
use roxblnfk\Soco\Collection\ActionHistory;
use roxblnfk\Soco\Collection\CommandQueue;
use roxblnfk\Soco\Gameplay\Rule\AbstractRule;
use roxblnfk\Soco\Level\TilesModel;

class GameVisualizer
{
    public string $title = '';
    public string $state = '';

    protected TilesModel $levelTiles;
    protected CommandQueue $commandsQueue;
    protected ActionHistory $actionHistory;
    protected AbstractRule $gameRules;

    public function __construct(
        protected ActiveGameModel $activeGame,
        protected readonly WebView $webView,
    ) {
        $this->commandsQueue = $activeGame->getCommandsQueue();
        $this->levelTiles = $activeGame->getLevelTiles();
        $this->actionHistory = $activeGame->getActionHistory();
        $this->gameRules = $activeGame->getGameRules();
    }

    public function update()
    {
        $state = $this->state ?: $this->gameRules->stateString;

        $json = \json_encode($this->levelTiles);
        $this->webView->eval(<<<JS
            renderLevel({$json}, "{$state}");
            JS);
        $finished = $this->activeGame->isFinished();
        if (is_bool($finished)) {
            tr($finished ? '  > VICTORY! <  ' : '  > DEFEAT! <  ');
        }
    }
}
