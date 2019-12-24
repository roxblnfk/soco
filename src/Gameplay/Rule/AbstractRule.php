<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Gameplay\Rule;

use roxblnfk\Soco\Gameplay\Action\AbstractAction;
use roxblnfk\Soco\Gameplay\Event\AbstractEvent;
use roxblnfk\Soco\Collection\ActionHistory;
use roxblnfk\Soco\Level\TilesModel;

abstract class AbstractRule
{
    protected TilesModel $levelTiles;
    protected ActionHistory $actionHistory;
    /** @var string|string[] UI string */
    public $stateString = '';

    public function __construct(TilesModel $levelTiles, ActionHistory $history) {
        $this->levelTiles = $levelTiles;
        $this->actionHistory = $history;
    }

    public function onLevelStart(): ?AbstractEvent
    {
        return null;
    }
    /**
     * @param AbstractAction $action
     * @return AbstractEvent|null
     *         You can return StopActionEvent if the event should not be applied
     */
    public function beforeAction(AbstractAction $action): ?AbstractEvent
    {
        return null;
    }

    public function afterAction(AbstractAction $action): ?AbstractEvent
    {
        return null;
    }
}
