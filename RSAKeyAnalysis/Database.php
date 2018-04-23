<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 28/07/2016
 * Time: 09:54
 */

namespace RSAKeyAnalysis;


use PDO;

class Database
{
    /**
     * @var PDO
     */
    private $db;

    /**
     * @var ClassificationTable
     */
    private $classificationTable;

    /**
     * Database constructor.
     * @param $databaseName
     * @param ClassificationTable $classificationTable
     */
    public function __construct($databaseName, ClassificationTable $classificationTable)
    {
        $this->classificationTable = $classificationTable;
        $this->db = new PDO('sqlite:' . $databaseName . '.sqlite3');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createRequiredTabled();
    }

    private function createRequiredTabled() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS post (
                         id INTEGER PRIMARY KEY AUTOINCREMENT, 
                         ct_id TEXT,
                         correct BOOLEAN,
                         source TEXT,
                         ip TEXT,
                         time INTEGER);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS key (
                         id INTEGER PRIMARY KEY AUTOINCREMENT, 
                         post_id INTEGER,
                         key TEXT);");
    }

    /**
     * @return string
     */
    public function createPost() {
        $insert = "INSERT INTO post (ct_id, ip, time) VALUES (:ctid, :ip, :time);";
        $stmt = $this->db->prepare($insert);

        $ct_id = $this->classificationTable->getDate();
        $time = time();
        $stmt->bindParam(':ctid', $ct_id);
        $stmt->bindParam(':ip', self::getClientIP());
        $stmt->bindParam(':time', $time, PDO::PARAM_INT);

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * @param $post_id
     * @param $keyText
     * @return string
     */
    public function createKey($post_id, $keyText) {
        $insert = "INSERT INTO key (post_id, key) VALUES (:postid, :key);";
        $stmt = $this->db->prepare($insert);
        $stmt->bindParam(':postid', $post_id);
        $stmt->bindParam(':key', $keyText);
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * @param $post_id
     * @param $correct
     * @param null $source
     */
    public function setCorrectSource($post_id, $correct, $source = null) {
        $update = "UPDATE post SET correct = :correct, source = :source WHERE id = :postid;";
        $stmt = $this->db->prepare($update);
        $stmt->bindParam(':postid', $post_id);
        $stmt->bindParam(':correct', $correct, PDO::PARAM_BOOL);
        $stmt->bindParam(':source', $source);
        $stmt->execute();
    }

    /**
     * @param $post_id
     * @return mixed
     */
    public function getPost($post_id) {
        $select = "SELECT * FROM post WHERE id = :postid;";
        $stmt = $this->db->prepare($select);
        $stmt->bindParam(':postid', $post_id);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return reset($result);
    }

    /**
     * @param $classificationKeysLimit
     * @param $classificationTimeLimit
     * @return mixed
     */
    public function getMaxPossibleClassifications($classificationKeysLimit, $classificationTimeLimit) {
        $select = "SELECT COUNT(*) AS c FROM post INNER JOIN key ON key.post_id = post.id WHERE ip = :ip AND time >= :time;";
        $time = time() - $classificationTimeLimit;
        $stmt = $this->db->prepare($select);
        $stmt->bindParam(':ip', self::getClientIP());
        $stmt->bindParam(':time', $time, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $classificationKeysLimit - $result["c"];
    }

    /**
     * @return string
     */
    private static function getClientIP() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
}