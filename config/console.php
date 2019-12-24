<?php

use Psr\Container\ContainerInterface;
use roxblnfk\Soco\Entity\LevelGroup;
use roxblnfk\Soco\Repository\FS\LevelGroupRepository;
use roxblnfk\Soco\Repository\FS\LevelRepository;
use roxblnfk\Soco\Repository\LevelGroupRepositoryInterface;
use roxblnfk\Soco\Repository\LevelRepositoryInterface;

return [
    #config
    'path.levels' => dirname(__DIR__) . '/data/levels',

    LevelGroupRepositoryInterface::class => DI\get(LevelGroupRepository::class),
    LevelRepositoryInterface::class      => DI\get(LevelRepository::class),

    LevelGroupRepository::class => function (ContainerInterface $c) {
        $path = $c->get('path.levels');

        # scan dir
        $dir = dir($path);
        $groups = [];
        while (false !== ($name = $dir->read())) {
            $filePath = "{$path}/{$name}";
            if (!is_file($filePath)) {
                continue;
            }
            $group = new LevelGroup();
            $group->groupName = $name;
            $group->description = $filePath;
            try {
                $levels = LevelGroupRepository::parseTxtSocoLevelFile($filePath, $group);
                $group->levels = new LevelRepository($levels);
            } catch (\Exception $e) {
                continue;
            }
            $groups[] = $group;
        }
        $dir->close();

        return new LevelGroupRepository($groups);
    },
];
