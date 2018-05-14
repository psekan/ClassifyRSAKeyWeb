<?php
namespace ClassifyRSA;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CliGroupsCommand extends Command
{
    /**
     * @var ClassificationModel
     */
    private $classificationModel;

    public function __construct(ClassificationModel $classificationModel)
    {
        parent::__construct();
        $this->classificationModel = $classificationModel;
    }

    protected function configure()
    {
        $this->setName('cli:groups')
            ->setDescription('Information about classification table.')
            ->addOption('prior', 'p', InputOption::VALUE_REQUIRED, 'Prior probability for sources: equal, tls, pgp.', 'equal');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $prior = $input->getOption('prior');
        switch ($prior) {
            case 'tls': break;
            case 'pgp': break;
            default: $prior = 'equal'; break;
        }
        $groups = $this->classificationModel->getClassificationSources($prior);
        $table = new Table($output);
        $table->setHeaders(['Group name','Sources']);
        foreach ($groups as $name => $sources) {
            $first = true;
            foreach ($sources as $source) {
                $table->addRow([($first ? $name : ''), $source]);
                $first = false;
            }
        }
        $table->render();
    }
}