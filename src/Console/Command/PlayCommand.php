<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Console\Command;

use roxblnfk\Soco\ActiveGameModel;
use roxblnfk\Soco\Console\Helper\Screen;
use roxblnfk\Soco\Control\ConsoleControl;
use roxblnfk\Soco\Entity\Level;
use roxblnfk\Soco\GameVisualizer;
use roxblnfk\Soco\Repository\LevelGroupRepositoryInterface;
use roxblnfk\Soco\Repository\LevelRepositoryInterface;
use roxblnfk\Soco\SymbolMap\ConsoleColorSymbolMap;
use roxblnfk\Soco\SymbolMap\ConsoleSymbolMap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PlayCommand extends Command
{
    protected static $defaultName = 'play';

    protected SymfonyStyle $io;
    protected array $screen = [];

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

    protected function execute(InputInterface $input, OutputInterface $output)
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

        $controlsManager = new ConsoleControl($game->getCommandsQueue());

        do {
            $visualizer->update();
            $screen->redraw(true);
            // $screen->matrix = array_map('shuffle', $screen->matrix);
            # get input
            $input = fgets($inputStream);
            $controlsManager->sendSignal($input);
            $game->process();
        } while (true);


        $screen->redraw();


        print_r($screen->getScreenSize());

        if ($levelId) {
            $this->io->note(sprintf('You passed an argument: %s', $levelId));
        }

        // if ($input->getOption('option1')) {
        //     // ...
        // }

        $this->io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}
