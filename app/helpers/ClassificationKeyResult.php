<?php
/**
 * Created by PhpStorm.
 * User: peter
 * Date: 5/2/2018
 * Time: 3:43 PM
 */

namespace ClassifyRSA\Helpers;


use JsonSerializable;
use RSAKeyAnalysis\ClassificationRow;
use RSAKeyAnalysis\RSAKey;

class ClassificationKeyResult implements JsonSerializable
{
    /**
     * @var string
     */
    private $keyText;

    /**
     * @var boolean
     */
    private $duplicated;

    /**
     * @var ClassificationRow
     */
    private $classificationRow;

    /**
     * @var string
     */
    private $identification;

    /**
     * @var boolean
     */
    private $mostAnonymous;

    /**
     * @var RSAKey
     */
    private $rsaKey;

    /**
     * @var array
     */
    private $orderedResults;

    /**
     * ClassificationKeyResult constructor.
     * @param string $keyText
     * @param bool $duplicated
     * @param ClassificationRow $classificationRow
     * @param string $identification
     * @param bool $mostAnonymous
     * @param RSAKey $rsaKey
     * @param array $orderedResults
     */
    public function __construct($keyText, $duplicated, $classificationRow, $identification, $mostAnonymous, $rsaKey, array $orderedResults)
    {
        $this->keyText = $keyText;
        $this->duplicated = $duplicated;
        $this->classificationRow = $classificationRow;
        $this->identification = $identification;
        $this->mostAnonymous = $mostAnonymous;
        $this->rsaKey = $rsaKey;
        $this->orderedResults = $orderedResults;
    }

    public function jsonSerializeOrderedResults() {
        $results = $this->getOrderedResults();
        array_walk($results, function(&$value, $key) {
            $value = ['group' => $key, 'value' => $value];
        });
        return array_values($results);
    }

    public function jsonSerialize()
    {
        return [
            'identification' => $this->getIdentification(),
            'orderedResults' => $this->jsonSerializeOrderedResults(),
            'rsaKey' => $this->getRsaKey(),
            'mostAnonymous' => $this->isMostAnonymous(),
            'duplicated' => $this->isDuplicated()
        ];
    }

    /**
     * @return string
     */
    public function getKeyText()
    {
        return $this->keyText;
    }

    /**
     * @return bool
     */
    public function isDuplicated()
    {
        return $this->duplicated;
    }

    /**
     * @return ClassificationRow
     */
    public function getClassificationRow()
    {
        return $this->classificationRow;
    }

    /**
     * @return string
     */
    public function getIdentification()
    {
        return $this->identification;
    }

    /**
     * @return bool
     */
    public function isMostAnonymous()
    {
        return $this->mostAnonymous;
    }

    /**
     * @return RSAKey
     */
    public function getRsaKey()
    {
        return $this->rsaKey;
    }

    /**
     * @return array
     */
    public function getOrderedResults()
    {
        return $this->orderedResults;
    }
}