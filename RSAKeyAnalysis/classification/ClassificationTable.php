<?php
namespace RSAKeyAnalysis;

require_once __DIR__ . "/../Math/BigInteger.php";
require_once __DIR__ . "/../common/Comparators.php";
require_once __DIR__ . "/../common/RomanNumber.php";
require_once __DIR__ . "/ClassificationRow.php";

use Math_BigInteger as BigInteger;
use RSAKeyAnalysis\common\RomanNumber;

/**
 * @author Peter Sekan, peter.sekan@mail.muni.cz
 * @version 07.02.2016
 */
class ClassificationTable {
    private $table = array();
    private $groups = array();
    private $date = "";
    private $groupsWeights = array();

    /**
     * @var IdentificationGenerator
     */
    private $identificationGenerator;

    public function __construct($tableGrouped, IdentificationGenerator $identificationGenerator, $date, $groupWeight = array()) {
        $this->date = $date;
        $this->identificationGenerator = $identificationGenerator;
        $normalized = array();

        $i = 0;
        foreach (array_keys($tableGrouped) as $group) {
            $groupName = RomanNumber::Int2Roman($i+1);
            $this->groups[$groupName] = $group;
            $this->groupsWeights[$groupName] = (array_key_exists($group, $groupWeight) ? $groupWeight[$group] : "1");
            $i++;

            $sum = array_sum(array_values($tableGrouped[$group]));
            $identifications = array();
            foreach ($tableGrouped[$group] as $entryKey => $entryValue) {
                $identifications[$entryKey] = ($entryValue * 100.0)/$sum;
            }
            $normalized[$groupName] = $identifications;
        }

        $allIdentifications = array();
        foreach (array_values($tableGrouped) as $map) {
            $allIdentifications = array_unique(array_merge($allIdentifications, array_keys($map)));
        }
        sort($allIdentifications);

        foreach ($allIdentifications as $identification) {
            $row = array();
            foreach (array_keys($this->groups) as $groupName) {
                if (array_key_exists($identification, $normalized[$groupName])) {
                    $row[$groupName] = bcmul(strval($normalized[$groupName][$identification]), $this->groupsWeights[$groupName]);
                }
            }
            $classificationRow = new ClassificationRow($row);
            $classificationRow->normalize();
            $this->table[$identification] = $classificationRow;
        }
    }

    /**
     * @param RSAKey $key
     * @return ClassificationRow
     */
    public function classifyKey(RSAKey $key) {
        $identification = $this->generationIdentification($key);
        if (!array_key_exists($identification, $this->table)) return null;
        return $this->table[$identification];
    }

    /**
     * @param RSAKey $key
     * @return string
     */
    public function generationIdentification(RSAKey $key) {
        return $this->identificationGenerator->generationIdentification($key);
    }

    /**
     * @param $identification
     * @return ClassificationRow
     */
    public function classifyIdentification($identification) {
        if (!array_key_exists($identification, $this->table)) return null;
        return $this->table[$identification];
    }

    /**
     * Get sources in group
     *
     * @param string $groupName name of group
     * @return array set of source names
     */
    public function getGroupSources($groupName) {
        if (!array_key_exists($groupName, $this->groups)) return array();
        return json_decode($this->groups[$groupName], true);
    }

    /**
     * Get names of groups
     *
     * @return array set of groups names
     */
    public function getGroupsNames() {
        return array_keys($this->groups);
    }

    /**
     * @return array
     */
    public function getTable() {
        return $this->table;
    }

    /**
     * @param string $groupName
     * @return ClassificationRow[]
     */
    public function getClassificationRowsForGroup($groupName) {
        $rows = array();
        foreach (array_values($this->table) as $row) {
            /** @var ClassificationRow $row */
            if ($row->getSource($groupName) != null) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function exportToCsvFormat($outFileName) {
        $content = "Group name;Group sources" . PHP_EOL;
        
        foreach ($this->getGroupsNames() as $groupName) {
            $content .= $groupName . ";" . implode(";", $this->getGroupSources($groupName)) . PHP_EOL;
        }
        $content .= PHP_EOL;

        $content .= "Bits;" . implode(";", $this->getGroupsNames()) . PHP_EOL;
        foreach ($this->table as $key => $val) {
            /** @var ClassificationRow $val */
            $content .= $key;
            foreach ($this->getGroupsNames() as $group) {
                $value = $val->getSource($group);
                if ($value == null) {
                    $content .= ";-";
                }
                else {
                    $content .= ";" . doubleval(bcmul($value, "100"));
                }
            }
            $content .= PHP_EOL;
        }
            
        if(file_put_contents($outFileName, $content) === false) {
            throw new \Exception("Cannot save to file '" . $outFileName . "'");
        }
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }
}
