<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Repository\FS;

use roxblnfk\Soco\Entity\Level;
use roxblnfk\Soco\Entity\LevelGroup;
use roxblnfk\Soco\Level\TilesModel;
use roxblnfk\Soco\Repository\LevelGroupRepositoryInterface;

class LevelGroupRepository extends BaseObjectRepository implements LevelGroupRepositoryInterface
{
    protected function loadFromFS()
    {

    }

    /**
     * @param string     $filePath
     * @param LevelGroup $group
     * @return array
     * @throws \Exception
     */
    public static function parseTxtSocoLevelFile(string $filePath, ?LevelGroup $group = null): array
    {
        $levels = [];
        $content = file_get_contents($filePath);
        $boxes = explode("\r\n\r\n", $content);

        $groupLines = [];
        $levelsBody = false;

        foreach ($boxes as $box) {
            $box = trim($box);
            if ($box === '') {
                continue;
            }
            $lines = explode("\n", $box);
            $mapMode = true;
            $mapLines = [];
            $header = 'comment';
            $mapHeaders = ['comment' => ''];
            foreach ($lines as $line) {
                $line = trim($line);
                # get levels symbols
                if ($mapMode)
                    if (strlen($line) && in_array($line[0], ['-', '#'])) {
                        $mapLines[] = $line;
                        continue;
                    } else {
                        $mapMode = false;
                    }
                # get headers
                $matches = [];
                if (preg_match('/([\w\s]+)\:\s*(.*)/ui', $line, $matches)) {
                    $header = strtolower($matches[1]);
                    $mapHeaders[$header] = $matches[2];
                } else {
                    $mapHeaders[$header] .= " {$line}";
                }
            }
            if (!$mapLines) {
                if (!$levelsBody) {
                    $groupLines[] = $box;
                }
                continue;
            }
            $levelsBody = true;
            $level = new Level();

            $level->tiles = TilesModel::createFromString(implode("\r\n", $mapLines), false);
            $level->author = $mapHeaders['author'] ?? '';
            $level->levelName = $mapHeaders['name'] ?? $mapHeaders['title'] ??  '';
            $level->description = $mapHeaders['description'] ?? $mapHeaders['comment'] ?? '';
            $level->createdAt = new \DateTimeImmutable($mapHeaders['date'] ?? null);
            if ($group !== null) {
                $level->levelGroup = $group;
            }
            $levels[] = $level;
        }
        if ($group !== null) {
            $group->description = implode("\r\n", $groupLines);
        }
        return $levels;
    }
}
