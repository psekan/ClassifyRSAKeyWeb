<?php
namespace ClassifyRSA;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CliClassifyCommand extends Command
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
        $this->setName('cli:classify')
            ->setDescription('Classify RSA keys.')
            ->addArgument('file', InputArgument::REQUIRED, 'File with RSA keys')
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
        $fileWithKeys = $input->getArgument('file');
        if (!file_exists($fileWithKeys)) {
            $output->writeln('File `'.$fileWithKeys.'` does not exist.');
            return;
        }
        $results = $this->classificationModel->classifyKeys(file_get_contents($fileWithKeys), null, $prior);

        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');
        $formattedBlock = $formatter->formatBlock([
            'Correct keys:   ' . $results->getCorrectKeys(),
            'Duplicate keys: ' . $results->getDuplicateKeys(),
            ''
        ], 'info');
        $output->writeln($formattedBlock);

        $table = new Table($output);
        $table->setHeaders(['Group name','Result']);
        foreach ($results->getOrderedClassificationContainerResults() as $source => $value) {
            if ($value == 0) continue;
            $table->addRow([$source, number_format(doubleval($value)*100,2) . ' %']);
        }
        $table->render();
    }
}