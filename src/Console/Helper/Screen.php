<?php
/**
 * @author roxblnfk
 * Date: 24.11.2018
 */

namespace roxblnfk\Soco\Console\Helper;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

class Screen
{
    private OutputInterface $output;
    private Terminal $terminal;
    private bool $overwrite = true;

    private bool $firstDraw = true;
    /** @var string[]|string[][] */
    public array $frame = [
        ['6','7','8'],
        ['5','0','1'],
        ['4','3','2'],
    ];

    protected int $redrawLines = 0;

    public function __construct(OutputInterface $output)
    {
        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        $this->output = $output;
        $this->terminal = new Terminal();

        if (!$this->output->isDecorated()) {
            // disable overwrite when output does not support ANSI codes.
            $this->overwrite = false;
        }
        // echo "overwrite: " . (int)$this->overwrite . "\r\n";
    }

    public function getScreenSize(bool $refresh = false): array
    {
        return [$this->terminal->getWidth(), $this->terminal->getHeight()];
    }

    public function removeLines(int $lines = 1): void
    {
        if ($lines <= 0)
            return;
        if ($this->output instanceof ConsoleSectionOutput) {
            $this->output->clear($lines);
        } else {
            # Move the cursor to the beginning of the line
            $this->output->write("\x0D");
            # Erase the line
            $this->output->write("\x1B[2K");
            # Erase previous lines
            $this->output->write(str_repeat("\x1B[1A\x1B[2K", $lines));
            // for ($i = 0; $i < $lines; ++$i)
            //     $this->output->write("\x1B[1A\x1B[2K");
        }
    }

    public function redraw(bool $cleanInputLine = false): void
    {
        if ($this->overwrite) {
            if (!$this->firstDraw) {
                $lines = $this->redrawLines;
                $this->removeLines($lines + (int)$cleanInputLine);
            }
        } else {
            $this->output->writeln('');
        }
        $this->redrawLines = count($this->frame);

        $this->firstDraw = false;
        foreach ($this->frame as $line) {
            if (is_array($line)) {
                foreach ($line as $pos => $symbol) {
                    $this->output->write(strlen($symbol) ? $symbol : ' ');
                }
            } else {
                $this->output->write($line);
            }
            $this->output->writeln('');
        }
    }
}
