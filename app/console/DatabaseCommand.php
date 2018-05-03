<?php
namespace ClassifyRSA;

use Nette\Database\Helpers;
use Nette\Database\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseCommand extends Command
{
    /**
     * @var Connection
     */
    private $database;

    /**
     * @var string
     */
    private $dumpFile;

    public function __construct($dumpFile, Connection $database)
    {
        parent::__construct();
        $this->database = $database;
        $this->dumpFile = $dumpFile;
    }

    protected function configure()
    {
        $this->setName('database:up')
            ->setDescription('Initialize database tables');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Helpers::loadFromFile(
            $this->database,
            $this->dumpFile
        );
        $output->writeln('Done');
    }
}