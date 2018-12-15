<?php

namespace ClassifyRSA\Presenters;

use ClassifyRSA\ClassificationModel;
use ClassifyRSA\DatabaseModel;
use Nette\Caching\Cache;
use Nette\Http\FileUpload;
use Tracy\Debugger;
use Tracy\ILogger;

class ApiPresenter extends Presenter
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

    /**
     * @throws \Nette\Application\AbortException
     */
    public function actionGroups() {
        $sources = $this->cache->call([$this->classificationModel, 'getClassificationSources'],'equal');
        $arr = [];
        foreach ($sources as $key => $val) {
            $arr[$key] = array_values($val);
        }
        $this->sendJson($arr);
    }

    /**
     * @throws \Nette\Application\AbortException
     */
    public function actionClassify() {
        $apriories = 'equal';
        $request = $this->getRequest();
        $keys = $request->getPost('keys');
        $files = $request->getFiles();
        if (!empty($files) && array_key_exists('files', $files)) {
            foreach ($files['files'] as $file) {
                /** @var FileUpload $file */
                $tmpFile = $file->getTemporaryFile();
                if ($keys == null) $keys = '';
                $keys .= PHP_EOL . PHP_EOL;
                $keys .= file_get_contents($tmpFile);
                unlink($tmpFile);
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
            }
            catch (\Throwable $ex) {
                Debugger::log($ex, ILogger::ERROR);
                $this->sendJson([
                    'code' => 500,
                    'error' => 'Cannot classify your keys. Please, try later again.'
                ]);
                return;
            }

            $sameSourceResults = $classificationResults->getOrderedClassificationContainerResults();
            array_walk($sameSourceResults, function(&$value, $key) {
                $value = ['group' => $key, 'value' => $value];
            });

            $this->sendJson([
                'code' => 200,
                'postId' => $postId,
                'uniqueKeys' => $classificationResults->getClassificationContainer() ? $classificationResults->getClassificationContainer()->getNumOfKeys() : 0,
                'correctKeys' => $classificationResults->getCorrectKeys(),
                'duplicateKeys' => $classificationResults->getDuplicateKeys(),
                'classifiedKeys' => $classificationResults->getKeysResults(),
                'containerResults' => array_values($sameSourceResults),
                'maxNumberOfClassificationExceeded' => ($maxUrlsClassifiable < count($classificationResults->getKeysResults()))
            ]);
        }
        else {
            $this->getHttpResponse()->setCode(400);
            $this->terminate();
        }
    }
}
