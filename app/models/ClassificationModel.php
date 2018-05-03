<?php
namespace ClassifyRSA;

use ClassifyRSA\Helpers\ClassificationKeyResult;
use ClassifyRSA\Helpers\ClassificationResults;
use Nette\Caching\Cache;
use Nette\SmartObject;
use RSAKeyAnalysis\ClassificationContainer;
use RSAKeyAnalysis\ClassificationRow;
use RSAKeyAnalysis\PublicKeyParser;
use RSAKeyAnalysis\RawTable;
use RSAKeyAnalysis\ClassificationTable;
use RSAKeyAnalysis\RSAKey;

class ClassificationModel
{
    use SmartObject;

    const CACHE_KEY_PREFIX = 'classification-table-';

    /**
     * @var string
     */
    private $classificationTableFile;

    /**
     * @var string
     */
    private $testKeysFile;

    /**
     * @var array
     */
    private $apriories;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * ClassificationModel constructor.
     * @param $classificationTableFile
     * @param $testKeysFile
     * @param $apriories
     * @param Cache $cache
     */
    function __construct($classificationTableFile, $testKeysFile, $apriories, Cache $cache)
    {
        $this->classificationTableFile = $classificationTableFile;
        $this->testKeysFile = $testKeysFile;
        $this->cache = $cache;
        $this->apriories = $apriories;
    }

    /**
     * @param $apriori
     * @return ClassificationTable
     * @throws \Throwable
     */
    public function computeClassificationTable($apriori) {
        $classificationTable = $this->cache->load(self::CACHE_KEY_PREFIX . $apriori);
        if ($classificationTable === null) {
            $apriories = [];
            if (array_key_exists($apriori,$this->apriories)) {
                $apriories = $this->apriories[$apriori];
            }
            $classificationTable = RawTable::load($this->classificationTableFile)->computeClassificationTable($apriories);
            $this->cache->save(self::CACHE_KEY_PREFIX . $apriori, $classificationTable);
        }
        return $classificationTable;
    }

    /**
     * @return bool|string
     */
    public function getPlaceholderKeys() {
        return file_get_contents($this->testKeysFile);
    }

    /**
     * @param $keysText
     * @param int $maxUrlsClassifiable
     * @param string $apriori
     * @return ClassificationResults
     * @throws \Throwable
     */
    public function classifyKeys($keysText, $maxUrlsClassifiable = 10, $apriori = '') {
        $classificationTable = $this->computeClassificationTable($apriori);
        $tableGroups = array_keys($this->getClassificationSources($apriori));

        PublicKeyParser::$maxUrlsClassifiable = $maxUrlsClassifiable;
        $keys = PublicKeyParser::parseMultiFromString($keysText);

        $correctKeys = 0;
        $duplicateKeys = 0;
        $top = null;
        foreach ($keys as $ink => $key) {
            /** @var RSAKey $rsaKey */
            $rsaKey = $key["key"];
            if ($rsaKey !== null) {
                $correctKeys++;

                $classified = $classificationTable->classifyKey($rsaKey);
                if ($classified === null) continue;

                $sa = "0.0";
                foreach ($classified->getValues() as $val) {
                    $sa = bcadd(bcmul($val, $val), $sa);
                }
                $p = count($classified->getValues());
                if ($top == null || $p > $top["p"] || ($p == $top["p"] && bccomp($sa, $top["s"]) == -1)) {
                    $top = array(
                        "i" => $ink,
                        "p" => $p,
                        "s" => $sa
                    );
                }

                foreach ($keys as $ink2 => $key2) {
                    /** @var RSAKey $rsaKey2 */
                    $rsaKey2 = $key2["key"];
                    if ($rsaKey2 === null) continue;
                    if ($ink <= $ink2) break;
                    if ($rsaKey->getModulus()->compare($rsaKey2->getModulus()) == 0) {
                        $duplicateKeys++;
                        $keys[$ink2]["duplicity"] = true;
                    }
                }
            }
        }
        if ($correctKeys > 1 && $top !== null) {
            $keys[$top["i"]]["ta"] = true;
        }

        $keysResults = [];
        /** @var \RSAKeyAnalysis\ClassificationContainer $classificationContainer */
        $classificationContainer = null;
        foreach ($keys as $key) {
            $row = null;
            if ($key["key"] !== null) {
                $row = $classificationTable->classifyKey($key["key"]);
            }
            if (!array_key_exists("duplicity", $key) && $row !== null) {
                if ($classificationContainer == null) {
                    $classificationContainer = new ClassificationContainer(1, $row);
                } else {
                    $classificationContainer->add(1, $row);
                }
            }

            $keysResults[] = new ClassificationKeyResult($key['text'],array_key_exists("duplicity", $key),$row,$key['identification'],array_key_exists("ta", $key) && $key['ta'], $key['key'], $this->orderedGroupsOfRows($row, $tableGroups));
        }
        return new ClassificationResults($keysResults,$correctKeys,$duplicateKeys,$classificationContainer, $this->orderedGroupsOfRows($classificationContainer->getRow(), $tableGroups));
    }

    /**
     * @param $apriories
     * @return array
     * @throws \Throwable
     */
    public function getClassificationSources($apriories) {
        $classificationTable = $this->computeClassificationTable($apriories);

        $classificationTableSources = [];
        foreach ($classificationTable->getGroupsNames() as $group) {
            $sources = $classificationTable->getGroupSources($group);
            natcasesort($sources);
            $classificationTableSources[$group] = implode(', ', $sources);
        }

        return $classificationTableSources;
    }

    /**
     * @param ClassificationRow $row
     * @param $groupsNames
     * @return array
     */
    private function orderedGroupsOfRows($row, $groupsNames) {
        $values = [];
        if ($row !== null) {
            $values = $row->getValues();
        }
        arsort($values);
        usort($groupsNames, '\RSAKeyAnalysis\common\RomanNumber::comparator');

        $sortedGroups = [];
        foreach ($values as $group => $value) {
            $sortedGroups[$group] = $value;
            $index = array_search($group, $groupsNames);
            unset($groupsNames[$index]);
        }
        foreach ($groupsNames as $group) {
            $sortedGroups[$group] = null;
        }
        return $sortedGroups;
    }
}