<?php

namespace ClassifyRSA\Presenters;

use ClassifyRSA\ClassificationModel;
use ClassifyRSA\DatabaseModel;
use Nette\Caching\Cache;
use Tracy\Debugger;
use Tracy\ILogger;

class ClassificationPresenter extends Presenter
{
    /**
     * @inject
     * @var ClassificationModel
     */
    public $classificationModel;

    /**
     * @inject
     * @var DatabaseModel
     */
    public $databaseModel;

    /**
     * @inject
     * @var Cache
     */
    public $cache;

    public function renderDefault() {
        $this->template->apriories = [
            "equal" => "1. Equal probability of all groups (default)",
            "tls" => "2. TLS prior probability",
            "pgp" => "3. PGP prior probability",
            "custom" => "4. Custom prior probability (not yet implemented, coming soon)"
        ];
    }

    public function actionDefault() {
        $request = $this->getRequest();

        /* Base post values */
        $apriories = $request->getPost('apriori') ?: 'equal';
        $postId = $request->getPost('post_id');
        $postTime = $request->getPost('time');
        $postSource = $request->getPost('source');
        $postCorrect = $request->getPost('correct');
        $postClassified = $request->getPost('classified');
        $keys = $request->getPost('keys');

        $tableGroups = $this->cache->call([$this->classificationModel, 'getClassificationSources'],$apriories);

        /* Feedback process */
        if ($postId !== null) {
            $post = $this->databaseModel->getPost($postId);

            /* If post's id and its time is correct */
            if ($post !== false && $post["time"] == $postTime) {
                $correct = (($postCorrect !== null && $postClassified !== null) && $postCorrect == $postClassified);

                /* Update correct source of a post */
                $this->databaseModel->setCorrectSource($postId, $correct, (!$correct ? "Classified: ". $postClassified . ", Correct: " . $postCorrect . ", " : "") . "Message: ". $postSource);

                /* Success flash message for user */
                $this->flashMessage('Thank you for your feedback.', 'success');
            }
            else {
                /* Flash message for user */
                $this->flashMessage('An error occurred. Please contact an administrator of the website.', 'danger');
            }
        }

        /* Process sent keys */
        if ($keys !== null) {
            try {
                /* Classify keys */
                $maxUrlsClassifiable = $this->databaseModel->getMaxPossibleClassifications();
                $classificationResults = $this->classificationModel->classifyKeys($keys, $maxUrlsClassifiable, $apriories);

                /* Create database entries */
                $postId = $this->databaseModel->createPost();
                foreach ($classificationResults->getKeysResults() as $key) {
                    if (!$key->isDuplicated()) {
                        $this->databaseModel->createKey($postId, $key->getKeyText());
                    }
                }

                /* Compute stats */
                $maxNumberOfClassificationExceeded = ($maxUrlsClassifiable < count($classificationResults->getKeysResults()));
                $sizeOfAlert = 12 / (1 + ($maxNumberOfClassificationExceeded ? 1 : 0) + ($classificationResults->getCorrectKeys() != count($classificationResults->getKeysResults()) ? 1 : 0) + ($classificationResults->getDuplicateKeys() > 0 ? 1 : 0));
                $uniqueKeys = $classificationResults->getClassificationContainer()->getNumOfKeys();
                $mostProbableGroup = $classificationResults->getClassificationContainer()->getRow()->getTopGroups(1);

                $probSuccessArray = array(
                    1 => "72%",
                    2 => "87%",
                    3 => "93%",
                    4 => "96%",
                    5 => "97%",
                    10 => "99%",
                    100 => "100.00%"
                );
                if ($uniqueKeys <= 5 || $uniqueKeys == 10 || $uniqueKeys == 100) $resProb = $probSuccessArray[$uniqueKeys];
                else if ($uniqueKeys < 10) $resProb = $probSuccessArray[5];
                else if ($uniqueKeys < 100) $resProb = $probSuccessArray[10];
                else $resProb = $probSuccessArray[100];

                /* Set template values */
                $this->template->post = $this->databaseModel->getPost($postId);
                $this->template->postId = $postId;
                $this->template->resProb = $resProb;
                $this->template->uniqueKeys = $uniqueKeys;
                $this->template->sizeOfAlert = $sizeOfAlert;
                $this->template->correctKeys = $classificationResults->getCorrectKeys();
                $this->template->duplicateKeys = $classificationResults->getDuplicateKeys();
                $this->template->classifiedKeys = $classificationResults->getKeysResults();
                $this->template->containerResults = $classificationResults->getOrderedClassificationContainerResults();
                $this->template->classificationContainerTopGroup = reset($mostProbableGroup);
                $this->template->maxNumberOfClassificationExceeded = $maxNumberOfClassificationExceeded;
            }
            catch (\Throwable $ex) {
                Debugger::log($ex, ILogger::ERROR);
                $this->flashMessage('Cannot classify your keys. Please, try later again.','danger');
            }
        }


        /* Set template values */
        $this->template->checkedApriori = $apriories;
        $this->template->keys = $request->getPost('keys') ?: $this->classificationModel->getPlaceholderKeys();
        $this->template->classificationTableSources = $tableGroups;
    }
}
