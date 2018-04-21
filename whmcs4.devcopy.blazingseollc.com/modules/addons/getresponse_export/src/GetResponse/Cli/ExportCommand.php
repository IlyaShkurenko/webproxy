<?php

namespace WHMCS\Module\Blazing\Export\GetResponse\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WHMCS\Database\Capsule;
use WHMCS\User\Client;

class ExportCommand extends Command
{

    public function configure()
    {
        $this->setName('getresponse:export')
            ->addOption(
                'force-init',
                null,
                InputOption::VALUE_OPTIONAL,
                'Export new users',
                0
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Specify limit for processing',
                0
            )
            ->addOption(
                'group',
                null,
                InputOption::VALUE_REQUIRED,
                'Specify group id of exported users',
                0
            )
            ->addOption(
                'add',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify group id of exported users',
                0
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = $input->getOption('limit');
        $group = $input->getOption('group');
        $forceInit = $input->getOption('force-init');
        $add = $input->getOption('add');
        (new \WHMCS\Module\Blazing\Export\GetResponse\Command\ExportCommand($limit, $group, $add, $forceInit))
            ->setConsoleOutput($output)
            ->export();
    }
}
