<?php
namespace RSAKeyAnalysis;

use Exception;
use GroupsComparator;
use Tracy\Debugger;
use Tracy\ILogger;

require_once __DIR__ . "/../Math/BigInteger.php";
require_once __DIR__ . "/../common/Comparators.php";
require_once __DIR__ . "/ClassificationContainer.php";
require_once __DIR__ . "/ClassificationRow.php";
require_once __DIR__ . "/ClassificationTable.php";
require_once __DIR__ . "/transformation/Transformation.php";
require_once __DIR__ . "/identification/IdentificationGenerator.php";

/**
 * @author Peter Sekan, peter.sekan@mail.muni.cz
 * @version 20.04.2016
 */
class RawTable {
    /**
     * Default value for max euclidean distance for create group
     */
    const DEFAULT_MAX_EUCLIDEAN_DISTANCE = 0.02;

    /**
     * Map of sources contains map of identification
     * Source -> Identification -> Count
     */
    private $table;

    private $identifications;

    private $groups;
    
    private $date;

    public function __construct($identifications, $groups, $date = null) {
        $this->identifications = $identifications;
        $this->groups = $groups;
        $this->date = $date ?: date("Y-m-d H:i:s");
    }

    public function getMaxEuclideanDistanceForGroup() {
        return $this->groups["maxEuclideanDistance"];
    }

    /**
     * @return String[]|null
     */
    public function getRepresentants() {
        if (!array_key_exists("representants", $this->groups)) return null;
        $array = $this->groups["representants"];
        $representants = array();
        foreach ($array as $representant) {
            $representants[] = $representant;
        }
        return $representants;
    }

    /**
     * @return mixed
     */
    public function getTable() {
        return $this->table;
    }

    /**
     * @return array
     */
    public function computeSourcesCount() {
        $counts = array();
        foreach ($this->table as $key => $value) {
            $counts[$key] = array_sum($value);
        }
        return $counts;
    }

    /**
     * @return array
     */
    public function computeEuclideanDistances() {
        $sourcesCount = $this->computeSourcesCount();
        $correlations = array();
        foreach (array_keys($this->table) as $X) {
            $correlationWithX = array();
            foreach (array_keys($this->table) as $Y) {
                $sum = 0.0;
                $allIdentifications = array_unique(array_merge(array_keys($this->table[$X]), array_keys($this->table[$Y])));
                foreach ($allIdentifications as $identification) {
                    $valX = $valY = 0.0;
                    if (array_key_exists($identification, $this->table[$X]))
                        $valX = $this->table[$X][$identification] / $sourcesCount[$X];
                    if (array_key_exists($identification, $this->table[$Y]))
                        $valY = $this->table[$Y][$identification] / $sourcesCount[$Y];
                    $sum += pow($valX - $valY, 2);
                }
                $correlationWithX[$Y] = sqrt($sum);
            }
            $correlations[$X] = $correlationWithX;
        }
        return $correlations;
    }

    public function computeSourceGroups() {
        $correlation = $this->computeEuclideanDistances();
        $newGroups = array();

        foreach (array_keys($correlation) as $source) {
            $newCluster = array();
            $newCluster[] = $source;
            $newGroups[] = $newCluster;
        }

        //Hierarchical clustering
        while (count($newGroups) > 1) {
            $minDistance = null;
            $minDistancePair = null;
            foreach ($newGroups as $cluster) {
                foreach ($newGroups as $cluster2) {
                    if ($cluster == $cluster2) continue;

                    $actualMinDistance = null;
                    foreach ($cluster as $clusterSource) {
                        foreach ($cluster2 as $cluster2Source) {
                            $val = $correlation[$clusterSource][$cluster2Source];
                            if ($actualMinDistance == null || $actualMinDistance > $val) {
                                $actualMinDistance = $val;
                            }
                        }
                    }

                    if ($minDistance == null || $minDistance > $actualMinDistance) {
                        $minDistance = $actualMinDistance;
                        $minDistancePair = array(
                            "key" => $cluster,
                            "value" => $cluster2
                        );
                    }
                }
            }


            if ($minDistance != null && $minDistance < $this->getMaxEuclideanDistanceForGroup()) {
                $remove = null;
                foreach ($newGroups as $key => $val) {
                    if ($val == $minDistancePair["value"]) {
                        $remove = $key;
                        break;
                    }
                }
                if ($remove == null) {
                    throw new Exception("Cannot remove some set during clustering.");
                }

                foreach ($newGroups as $key => $val) {
                    if ($val == $minDistancePair["key"]) {
                        $newGroups[$key] = array_unique(array_merge($minDistancePair["key"], $minDistancePair["value"]));
                        break;
                    }
                }

                unset($newGroups[$remove]);
            }
            else break;
        }

        //Use new sort
        $groups = array();
        foreach ($newGroups as $set) {
            $groups[] = $set;
        }
        usort($groups, "SetSizeComparator");

        return $groups;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function computeTableGrouped() {
        $groups = $this->computeSourceGroups();

        GroupsComparator::setGroupsComparator($this->getRepresentants());
        $classificationTableGrouped = array();
        foreach ($groups as $group) {
            $identificationsCount = array();
            foreach ($group as $source) {
                foreach ($this->table["$source"] as $identificationCountKey => $identificationCountValue) {
                    $val = 0;
                    if (array_key_exists($identificationCountKey, $identificationsCount)) $val = $identificationsCount[$identificationCountKey];
                    $val += $identificationCountValue;
                    $identificationsCount[$identificationCountKey] =  $val;
                }
            }
            sort($group);
            $classificationTableGrouped[json_encode($group)] = $identificationsCount;
        }
        uksort($classificationTableGrouped, array("\\GroupsComparator", "compare"));
        
        return $classificationTableGrouped;
    }

    /**
     * @param array $sourceWeight
     * @return ClassificationTable
     * @throws Exception
     */
    public function computeClassificationTable($sourceWeight = array()) {
        $tableGrouped = $this->computeTableGrouped();

        $transformations = array();
        foreach ($this->identifications as $identificationPart) {
            $transformations[] = Transformation::createFromIdentificationPart($identificationPart);
        }
        $identificationGenerator = new IdentificationGenerator($transformations);

        $groupsWeights = array();
        foreach (array_keys($tableGrouped) as $group) {
            $groupSources = json_decode($group);
            $groupWeight = null;
            foreach ($groupSources as $groupSource) {
                if (array_key_exists($groupSource, $sourceWeight)) {
                    $val = strval($sourceWeight[$groupSource]);
                    if ($groupWeight == null || bccomp($val, $groupWeight) == 1) {
                        $groupWeight = $val;
                    }
                }
            }
            $groupsWeights[$group] = $groupWeight ?: "1";
        }
        return new ClassificationTable($tableGrouped, $identificationGenerator, $this->date, $groupsWeights);
    }

    /**
     * @param $fileName
     * @throws \Exception
     */
    public function save($fileName) {
        $root = array();

        //Actual date
        $root["date"] = date("d-m-Y H:i:s");

        //Identifications
        $root["identifications"] = $this->identifications;

        //Groups
        $root["groups"] = $this->groups;

        //Table
        $root["table"] = $this->table;
        if (file_put_contents($fileName, json_encode($root)) === false) {
            throw new Exception("Cannot save table as json to file '" . $fileName . "'");
        }
    }

    /**
     * @param $fileName
     * @param string $typeFlag
     * @return RawTable
     * @throws \Exception
     */
    public static function load($fileName, $typeFlag = "") {
        $content = file_get_contents($fileName);
        if ($content === false) {
            throw new Exception("Cannot read table from file '" . $fileName . "'");
        }

        $root = json_decode($content, true);
        $identifications = $root["identifications"];
        $groups = $root["groups"];
        $date = $root["date"];

        $rawTable = new RawTable($identifications, $groups, $date);
        $rawTable->table = $root["table"];
        if ($typeFlag != "") {
            if (!array_key_exists("typeFlag", $root)) {
                Debugger::log("Classification with typeFlag required, but classification table does not contain typeFlag property.",ILogger::WARNING);
            }
            else {
                $tmpTable = [];
                foreach ($rawTable->table as $key => $value) {
                    if (array_key_exists($key, $root["typeFlag"]) && $root["typeFlag"][$key] == $typeFlag) {
                        $tmpTable[$key] = $value;
                    }
                }
                $rawTable->table = $tmpTable;
            }
        }
        return $rawTable;
    }

    public function copyTable() {
        $tableTemp = array();
        foreach ($this->table as $entrySourceKey => $entrySourceValue) {
            $identificationsCount = array();
            foreach ($entrySourceValue as $entryIdentificationKey => $entryIdentificationValue) {
                $identificationsCount[$entryIdentificationKey] = $entryIdentificationValue;
            }
            $tableTemp[$entrySourceKey] = $identificationsCount;
        }
        return $tableTemp;
    }

    /**
     * @param $numberOfKeysFromSource
     * @return array
     * @throws Exception
     */
    public function splitForTests($numberOfKeysFromSource) {
        $testsKeysTable = new RawTable($this->identifications, $this->groups);
        $withoutTestsKeysTable = new RawTable($this->identifications, $this->groups);

        //Copy table
        $tableTemp = $this->copyTable();

        //Remove keys for each source
        foreach (array_keys($this->table) as $source) {
            $identificationsCount = array();
            $numOfKeys = $numberOfKeysFromSource;
            $sumOfKeys = array_sum($tableTemp[$source]);
            if ($sumOfKeys < $numOfKeys) {
                throw new Exception("In table is less then " . $numOfKeys . " keys (" . $sumOfKeys . ").");
            }
            while ($numOfKeys > 0) {
                $randomIdentification = null;
                $keyPos = mt_rand(0, $sumOfKeys - 1);
                foreach ($tableTemp[$source] as $entryKey => $entryValue) {
                    if ($keyPos < $entryValue) {
                        $randomIdentification = $entryKey;
                        break;
                    }
                    $keyPos -= $entryValue;
                }
                if ($randomIdentification == null) {
                    throw new Exception("Cannot get random key from table.");
                }
                $keys = $tableTemp[$source][$randomIdentification];
                if ($keys == 1) {
                    unset($tableTemp[$source][$randomIdentification]);
                }
                else {
                    $tableTemp[$source][$randomIdentification] = $keys - 1;
                }

                $val = 0;
                if (array_key_exists($randomIdentification, $identificationsCount)) $val = $identificationsCount[$randomIdentification];
                $identificationsCount[$randomIdentification] = $val + 1;

                $sumOfKeys--;
                $numOfKeys--;
            }

            $testsKeysTable->table[$source] = $identificationsCount;
        }

        //Add others to another table
        $withoutTestsKeysTable->table = $tableTemp;

        return array(
            "key" => $testsKeysTable,
            "value" => $withoutTestsKeysTable
        );
    }

    /**
     * @param $outFile
     */
    public function exportToCsvFormat($outFile) {
        $identifications = array();
        foreach ($this->table as $map) {
            $identifications = array_unique(array_merge($identifications, array_keys($map)));
        }

        $content = ";" . implode(";", array_keys($this->table)) . PHP_EOL;
        foreach ($identifications as $identification) {
            $content .= $identification;
            foreach (array_keys($this->table) as $source) {
                $content .= ";" . (!array_key_exists($identification, $this->table[$source]) ? "0" : $this->table[$source][$identification]);
            }
            $content .= PHP_EOL;
        }
        file_put_contents($outFile, $content);
    }
}
