<?php

use Psr\Container\ContainerInterface;
use roxblnfk\Soco\Repository\LevelGroupRepository;

return [
    LevelGroupRepository::class => function (ContainerInterface $c) {
        $groups = [];
        return new LevelGroupRepository($groups);
    }
];
