<?php
namespace ClassifyRSA;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CMoCLApiKeyCommand extends Command
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
        $this->setName('cmocl:key')
            ->setDescription('Get API key for CMoCL interface.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $this->database->getAPIKey();
        if ($key === false) {
            $output->writeln('Key has not yet initialized.');
            return;
        }
        $output->writeln($key);
    }
}