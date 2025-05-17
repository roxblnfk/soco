<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Boson;

use Boson\Bridge\Static\FilesystemStaticAdapter;
use Boson\WebView\Event\WebViewRequest;
use Boson\WebView\WebViewCreateInfo;
use Boson\Window\WindowCreateInfo;
use roxblnfk\Soco\ActiveGameModel;
use roxblnfk\Soco\Control\WasdControl;
use roxblnfk\Soco\Repository\LevelGroupRepositoryInterface;
use roxblnfk\Soco\Repository\LevelRepositoryInterface;
use roxblnfk\Soco\SymbolMap\StandardSymbolMap;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        $app = new \Boson\Application(
            new \Boson\ApplicationCreateInfo(
                schemes: ['soco'],
                debug: false,
                window: new WindowCreateInfo(webview: new WebViewCreateInfo(contextMenu: true)),
            )
        );

        $level = $this->groupRepository->getRandom()->levels->getRandom();

        // $level->tiles->outputSymbolMap = ConsoleSymbolMap::class;
        $level->tiles->outputSymbolMap = StandardSymbolMap::class;

        $game = new ActiveGameModel(null, null, $level->tiles, null);
        $visualizer = new GameVisualizer($game, $app->webview);
        $title = [];
        strlen((string) $level->levelGroup?->groupName) and $title[] = $level->levelGroup->groupName;
        strlen($level->levelName) and $title[] = $level->levelName;
        $visualizer->title = implode(' -> ', $title);

        $controlsManager = new WasdControl($game->getCommandsQueue());


        $static = new FilesystemStaticAdapter(['resources/frontend']);
        $app->on(function (WebViewRequest $e) use ($static): void {
            $e->response = $static->lookup($e->request);
        });
        $app->webview->url = 'soco://localhost/index.html';


        $app->webview->bind('keyboard', static function (string $key) use ($controlsManager, $game, $visualizer): void {
            $controlsManager->sendSignal($key);
            $game->process();
            $visualizer->update();
        });
        $visualizer->update();

        $app->run();
        return 0;
    }
}
