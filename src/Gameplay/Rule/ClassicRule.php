<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Gameplay\Rule;

use roxblnfk\Soco\Gameplay\Action\AbstractAction;
use roxblnfk\Soco\Gameplay\Event\AbstractEvent;
use roxblnfk\Soco\Gameplay\Event\VictoryEvent;
use roxblnfk\Soco\Level\TileObjectModel;

class ClassicRule extends AbstractRule
{
    /** @var TileObjectModel[] */
    protected array $markers = [];
    /** @var TileObjectModel[] */
    protected array $blocks = [];

    public function onLevelStart(): ?AbstractEvent
    {
        $this->findMarkersAndBlocks();
        $this->calcCoverage();
        return null;
    }
    /**
     * Find all blocks and targets
     */
    protected function findMarkersAndBlocks()
    {
        $this->markers = [];
        $this->blocks = [];
        foreach ($this->levelTiles->tiles as $X => $colY) {
            foreach ($colY as $Y => $tile) {
                if (isset($tile[0]) && $tile[0]->id === 3)
                    $this->markers[] = $tile[0];
                if (isset($tile[1]) && $tile[1]->id === 5)
                    $this->blocks[] = $tile[1];
            }
        }
    }
    /**
     * Calc count of blocks on targets
     */
    public function calcCoverage(): int
    {
        $cover = 0;
        $tiles = &$this->levelTiles->tiles;
        foreach ($this->markers as $marker) {
            [$x, $y] = $marker->xy;
            if (isset($tiles[$x][$y][1]) && $tiles[$x][$y][1]->id === 5) {
                ++$cover;
            }
        }
        return $cover;
    }

    public function beforeAction(AbstractAction $action): ?AbstractEvent
    {
        return null;
    }

    public function afterAction(AbstractAction $action): ?AbstractEvent
    {
        $cover = $this->calcCoverage();
        $needCover = min(count($this->blocks), count($this->markers));
        $steeps = $this->actionHistory->count();
        $steep = $this->actionHistory->key();
        isset($steep) AND ++$steep;
        $steepStr = (int)$steep < $steeps ? "{$steep}/{$steeps}" : (int)$steep;
        $this->stateString = ["Covered: {$cover}/{$needCover}; ", "steeps: {$steepStr}"];

        if ($steep > 0 and $cover === $needCover)
            return new VictoryEvent();

        return null;
    }
}
