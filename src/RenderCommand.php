<?php
declare(strict_types=1);

namespace Visualizer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class RenderCommand extends Command
{
    protected static $defaultName = 'render';

    protected function configure(): void
    {
        $this
            ->addArgument('database', InputArgument::REQUIRED, 'Database name')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Database host', 'localhost')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'Database username', 'root')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Database password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $database = $input->getArgument('database');

        $repo = new Repository($input->getOption('host'), $input->getOption('user'), $input->getOption('password'));
        $builder = new Builder($repo);

        $path = dirname(__DIR__);
        $dotfile = $path . '/' . $database . '.dot';
        $pngfile = $path . '/' . $database . '.png';

        file_put_contents($dotfile, $builder->build($database));

        $process = new Process(['dot', '-Tpng', '-o' . $pngfile, '-v', $dotfile]);
        $process->run();

        return $process->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
    }
}
