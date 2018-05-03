<?php
namespace ClassifyRSA;

use ClassifyRSA\Helpers\ClientIP;
use Nette\Database\Connection;
use Nette\Database\Row;

class DatabaseModel
{
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

}