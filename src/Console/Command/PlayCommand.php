<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Console\Command;

use roxblnfk\Soco\ActiveGameModel;
use roxblnfk\Soco\Console\GameVisualizer;
use roxblnfk\Soco\Console\Helper\Screen;
use roxblnfk\Soco\Control\WasdControl;
use roxblnfk\Soco\Repository\LevelGroupRepositoryInterface;
use roxblnfk\Soco\Repository\LevelRepositoryInterface;
use roxblnfk\Soco\SymbolMap\ConsoleColorSymbolMap;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('play')]
class PlayCommand extends Command
{
    protected SymfonyStyle $io;
    protected LevelRepositoryInterface $LevelRepository;
    protected LevelGroupRepositoryInterface $groupRepository;

    public function __construct(LevelRepositoryInterface $LevelRepository, LevelGroupRepositoryInterface $groupRepository)
    {
        parent::__construct();
        $this->LevelRepository = $LevelRepository;
        $this->groupRepository = $groupRepository;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }
    protected function configure()
    {
        $this
            ->setDescription('Play level')
            ->addArgument('LevelId', InputArgument::OPTIONAL, 'ID of the level')
            // ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $levelId = $input->getArgument('LevelId');

        $screen = new Screen($output);
        # input stream
        if ($input instanceof StreamableInputInterface && $stream = $input->getStream()) {
            $inputStream = $stream;
        } else {
            $inputStream = STDIN;
        }

        $level = $this->groupRepository->getRandom()->levels->getRandom();

        // $level->tiles->outputSymbolMap = ConsoleSymbolMap::class;
        $level->tiles->outputSymbolMap = ConsoleColorSymbolMap::class;

        $game = new ActiveGameModel(null, null, $level->tiles, null);
        $visualizer = new GameVisualizer($game, $screen);
        $title = [];
        strlen($level->levelGroup->groupName) and $title[] = $level->levelGroup->groupName;
        strlen($level->levelName) and $title[] = $level->levelName;
        $visualizer->title = implode(' -> ', $title);

        $controlsManager = new WasdControl($game->getCommandsQueue());

        do {
            $visualizer->update();
            $screen->redraw(true);
            // $screen->matrix = array_map('shuffle', $screen->matrix);
            # get input
            $input = fgets($inputStream);
            $controlsManager->sendSignal($input);
            $game->process();
        } while (true);
    }
}
