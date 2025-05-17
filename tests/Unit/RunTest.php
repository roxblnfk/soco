<?php

namespace roxblnfk\Soco\Tests\Unit;

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use roxblnfk\Soco\Console\Command\PlayCommand;
use Symfony\Component\Console\Tester\CommandTester;

class RunTest extends TestCase {
    /**
     * For xdebug purposes
     */
    public function testRun()
    {
        # container
        $builder = new ContainerBuilder();
        $builder->addDefinitions(dirname(__DIR__, 2) . '/config/console.php');
        $container = $builder->build();

        $command = $container->get(PlayCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([], []);
    }
}
