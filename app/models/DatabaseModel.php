<?php
namespace ClassifyRSA;

use ClassifyRSA\Helpers\ClientIP;
use ClassifyRSA\Helpers\CMoCLRecord;
use Exception;
use Nette\Database\Connection;
use Nette\Database\Row;
use Nette\Database\UniqueConstraintViolationException;
use Tracy\Debugger;

class DatabaseModel
{
    const API_KEY = 'api_key';

    /**
     * @var Connection
     */
    private $database;

    /**
     * @var int
     */
    private $classificationKeysLimit;

    /**
     * @var int
     */
    private $classificationTimeLimit;

    public function __construct($classificationKeysLimit, $classificationTimeLimit, Connection $database)
    {
        $this->database = $database;
        $this->classificationKeysLimit = $classificationKeysLimit;
        $this->classificationTimeLimit = $classificationTimeLimit;
    }

    /**
     * @return string
     */
    public function createPost() {
        $this->database->query('INSERT INTO post', [
            'ip' => ClientIP::getClientIP(),
            'time' => time()
        ]);
        return $this->database->getInsertId();
    }

    /**
     * @param $post_id
     * @param $keyText
     * @return string
     */
    public function createKey($post_id, $keyText) {
        $this->database->query('INSERT INTO `key`', [
            'post_id' => $post_id,
            'key' => $keyText
        ]);
        return $this->database->getInsertId();
    }

    /**
     * @param $post_id
     * @param $correct
     * @param null $source
     */
    public function setCorrectSource($post_id, $correct, $source = null) {
        $this->database->query('UPDATE post SET', [
            'correct' => $correct,
            'source' => $source
        ], 'WHERE id = ?', $post_id);
    }

    /**
     * @param $post_id
     * @return Row|false
     */
    public function getPost($post_id) {
        return $this->database->fetch("SELECT * FROM post WHERE id = ?;", $post_id);
    }

    /**
     * @return int
     */
    public function getMaxPossibleClassifications() {
        $time = time() - $this->classificationTimeLimit;
        $result = $this->database->fetch("SELECT COUNT(*) AS c FROM post INNER JOIN `key` ON `key`.post_id = post.id WHERE ip = ? AND time >= ?;", ClientIP::getClientIP(), $time);
        return $this->classificationKeysLimit - $result["c"];
    }

    /**
     * @param CMoCLRecord $record
     */
    public function insertCMoCLRecord(CMoCLRecord $record) {
        if ($this->getCMoCLRecord($record->getSource(), $record->getPeriod(), $record->getDate()) !== null) {
            throw new UniqueConstraintViolationException("Record for this source, period and date already exists.");
        }
        $this->database->query('INSERT INTO `cmocl`', [
            'source' => $record->getSource(),
            'period' => $record->getPeriod(),
            'date' => $record->getDate(),
            'data' => $record->getEstimation()
        ]);
    }

    /**
     * @param string $source
     * @param string $period
     * @param string $date
     * @return CMoCLRecord|null
     */
    public function getCMoCLRecord($source, $period, $date) {
        $row = $this->database->fetch("SELECT * FROM cmocl WHERE source = ? AND period = ? AND `date` = ?;", $source, $period, $date);
        if ($row === false) {
            return null;
        }
        return new CMoCLRecord($row['source'], $row['period'], $row['date'], $row['data']);
    }

    /**
     * @param string $source
     * @param string $period
     * @param string $from
     * @param string $to
     * @return CMoCLRecord[]
     */
    public function getCMoCLRecordsFromTo($source, $period, $from, $to) {
        $rows = $this->database->query("SELECT * FROM cmocl WHERE source = ? AND period = ? AND `date` >= ? AND `date` <= ? ORDER BY `date`;", $source, $period, $from, $to);
        $results = [];
        foreach ($rows as $row) {
            $results[] = new CMoCLRecord($row['source'], $row['period'], $row['date'], $row['data']);
        }
        return $results;
    }

    /**
     * @return array
     */
    public function getCMoCLSources() {
        return $this->database->query("SELECT source FROM cmocl GROUP BY source ORDER BY source;")->fetchPairs(null, 'source');
    }

    /**
     * @param string $source
     * @param string $period
     * @return array
     */
    public function getCMoCLAvailableDates($source, $period) {
        return $this->database->query("SELECT `date` FROM cmocl WHERE source = ? AND period = ? GROUP BY `date` ORDER BY `date`;", $source, $period)->fetchPairs(null, 'date');
    }

    /**
     * @return string|false
     */
    public function setNewAPIKey() {
        try {
            $key = base64_encode(random_bytes(16));
            $this->database->query("DELETE FROM `setting` WHERE `key` = ?;", self::API_KEY);
            $this->database->query('INSERT INTO `setting`', [
                'key' => self::API_KEY,
                'value' => $key
            ]);
            return $key;
        }
        catch (Exception $ex) {
            Debugger::log($ex);
            return false;
        }
    }

    /**
     * @return string|false
     */
    public function getAPIKey() {
        return $this->database->fetchField("SELECT `value` FROM `setting` WHERE `key` = ?;", self::API_KEY);
    }
}