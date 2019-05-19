<?php

namespace ClassifyRSA\Presenters;

use ClassifyRSA\DatabaseModel;
use ClassifyRSA\Helpers\CMoCLRecord;
use Nette\Application\AbortException;
use Nette\Caching\Cache;
use Nette\Database\UniqueConstraintViolationException;

class CMoCLPresenter extends Presenter
{
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
     * @param $message
     * @throws AbortException
     */
    protected function badRequest($message) {
        $this->getHttpResponse()->setCode(400);
        $this->sendJson(array('code' => 400, 'message' => $message));
    }

    /**
     * @throws AbortException
     */
    protected function forbidden() {
        $this->getHttpResponse()->setCode(403);
        $this->terminate();
    }

    /**
     * @throws AbortException
     */
    public function actionBase() {
        $request = $this->getHttpRequest();
        if ($request->isMethod('GET')) {
            $this->sendJson($this->databaseModel->getCMoCLSources());
        }
        else if ($request->isMethod('POST')) {
            $authorization = $request->getHeader('Authorization');
            if ($authorization === null) {
                $this->badRequest('Missed authorization.');
            }
            $authArray = explode(" ", $authorization);
            if (count($authArray) != 2 || $authArray[0] != "Bearer") {
                $this->badRequest('Unknown authorization.');
            }
            $apiKey = $this->databaseModel->getAPIKey();
            if ($apiKey === false || $authArray[1] != $apiKey) {
                $this->forbidden();
            }

            $body = $request->getRawBody();
            $record = CMoCLRecord::jsonDeserialize($body);
            if ($record === null) {
                $this->badRequest('Wrong format of record.');
            }
            try {
                $this->databaseModel->insertCMoCLRecord($record);
            }
            catch (UniqueConstraintViolationException $ex) {
                $this->getHttpResponse()->setCode(409);
                $this->terminate();
            }
            $this->getHttpResponse()->setCode(204);
            $this->terminate();
        }
        $this->badRequest('Unknown http method.');
    }

    /**
     * @param string $source
     * @param string $period
     * @throws AbortException
     */
    public function actionDates($source, $period) {
        if (!CMoCLRecord::isPeriodValid($period)) {
            $this->getHttpResponse()->setCode(400);
            $this->sendJson(array('code' => 400, 'message' => 'Unknown period.'));
        }
        if (!in_array($source, $this->databaseModel->getCMoCLSources())) {
            $this->getHttpResponse()->setCode(400);
            $this->sendJson(array('code' => 400, 'message' => 'Unknown source.'));
        }
        $this->sendJson($this->databaseModel->getCMoCLAvailableDates($source, strtolower($period)));
    }

    /**
     * @param string $source
     * @param string $period
     * @param string $date
     * @throws AbortException
     */
    public function actionFindRecord($source, $period, $date) {
        if (!CMoCLRecord::isPeriodValid($period)) {
            $this->getHttpResponse()->setCode(400);
            $this->sendJson(array('code' => 400, 'message' => 'Unknown period.'));
        }
        if (!in_array($source, $this->databaseModel->getCMoCLSources())) {
            $this->getHttpResponse()->setCode(400);
            $this->sendJson(array('code' => 400, 'message' => 'Unknown source.'));
        }
        $record = $this->databaseModel->getCMoCLRecord($source, strtolower($period), $date);
        if ($record === null) {
            $this->getHttpResponse()->setCode(404);
            $this->terminate();
        }
        $this->sendJson($record);
    }

    /**
     * @param string $source
     * @param string $period
     * @param string $from
     * @param string $to
     * @throws AbortException
     */
    public function actionFindRecords($source, $period, $from, $to) {
        if (!CMoCLRecord::isPeriodValid($period)) {
            $this->getHttpResponse()->setCode(400);
            $this->sendJson(array('code' => 400, 'message' => 'Unknown period.'));
        }
        if (!in_array($source, $this->databaseModel->getCMoCLSources())) {
            $this->getHttpResponse()->setCode(400);
            $this->sendJson(array('code' => 400, 'message' => 'Unknown source.'));
        }
        $records = $this->databaseModel->getCMoCLRecordsFromTo($source, strtolower($period), $from, $to);
        $this->sendJson($records);
    }
}
