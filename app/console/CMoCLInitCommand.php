<?php
namespace ClassifyRSA;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CMoCLInitCommand extends Command
{
    /**
     * @var DatabaseModel
     */
    private $database;

    public function __construct(DatabaseModel $database)
    {
        parent::__construct();
        $this->database = $database;
    }

    protected function configure()
    {
        $this->setName('cmocl:init')
            ->setDescription('Initialize API key for CMoCL interface.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $this->database->setNewAPIKey();
        if ($key === false) {
            $output->writeln('An error occurred.');
            return;
        }
        $output->writeln($key);
    }
}