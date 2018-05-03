<?php
namespace RSAKeyAnalysis;

require_once __DIR__ . "/../Math/BigInteger.php";
require_once __DIR__ . "/ClassificationRow.php";

/**
 * @author Peter Sekan, peter.sekan@mail.muni.cz
 * @version 15.02.2016
 */
class ClassificationContainer {
    private $numOfRows = 0;
    private $numOfKeys = 0;

    /**
     * @var ClassificationRow
     */
    private $row;

    /**
     * ClassificationContainer constructor.
     * @param int $numOfDuplicityKeys
     * @param ClassificationRow $row
     */
    public function __construct($numOfDuplicityKeys, $row) {
        $this->numOfRows = 1;
        $this->numOfKeys = $numOfDuplicityKeys;
        $this->row = $row;
    }

    /**
     * @param $numOfDuplicityKeys
     * @param $row
     */
    public function add($numOfDuplicityKeys, $row) {
        $this->numOfRows++;
        $this->numOfKeys += $numOfDuplicityKeys;
        $this->row = $this->row->computeWithSameSource($row);
    }

    /**
     * @return int
     */
    public function getNumOfRows() {
        return $this->numOfRows;
    }

    /**
     * @return int
     */
    public function getNumOfKeys() {
        return $this->numOfKeys;
    }

    /**
     * @return ClassificationRow
     */
    public function getRow() {
        return $this->row;
    }
}
