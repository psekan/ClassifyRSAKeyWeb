<?php
/**
 * Created by PhpStorm.
 * User: peter
 * Date: 5/2/2018
 * Time: 3:42 PM
 */

namespace ClassifyRSA\Helpers;


use RSAKeyAnalysis\ClassificationContainer;

class ClassificationResults
{
    /**
     * @var ClassificationKeyResult[]
     */
    private $keysResults;

    /**
     * @var int
     */
    private $correctKeys;

    /**
     * @var int
     */
    private $duplicateKeys;

    /**
     * @var ClassificationContainer
     */
    private $classificationContainer;

    /**
     * @var array
     */
    private $orderedClassificationContainerResults;

    /**
     * ClassificationResults constructor.
     * @param ClassificationKeyResult[] $keysResults
     * @param int $correctKeys
     * @param int $duplicateKeys
     * @param ClassificationContainer $classificationContainer
     * @param array $orderedClassificationContainerResults
     */
    public function __construct(array $keysResults, $correctKeys, $duplicateKeys, ClassificationContainer $classificationContainer, array $orderedClassificationContainerResults)
    {
        $this->keysResults = $keysResults;
        $this->correctKeys = $correctKeys;
        $this->duplicateKeys = $duplicateKeys;
        $this->classificationContainer = $classificationContainer;
        $this->orderedClassificationContainerResults = $orderedClassificationContainerResults;
    }

    /**
     * @return ClassificationKeyResult[]
     */
    public function getKeysResults()
    {
        return $this->keysResults;
    }

    /**
     * @return int
     */
    public function getCorrectKeys()
    {
        return $this->correctKeys;
    }

    /**
     * @return int
     */
    public function getDuplicateKeys()
    {
        return $this->duplicateKeys;
    }

    /**
     * @return ClassificationContainer
     */
    public function getClassificationContainer()
    {
        return $this->classificationContainer;
    }

    /**
     * @return array
     */
    public function getOrderedClassificationContainerResults()
    {
        return $this->orderedClassificationContainerResults;
    }
}