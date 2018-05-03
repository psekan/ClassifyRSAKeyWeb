<?php
namespace RSAKeyAnalysis;

require_once __DIR__ . "/../common/Comparators.php";

//SET BCSCALE
\bcscale(10);

/**
 * @author Peter Sekan, peter.sekan@mail.muni.cz
 * @version 07.02.2016
 */
class ClassificationRow {
    private $sources = array();

//    public function __construct(array $sources = array(), array $values = array()) {
//        if (count($sources) != count($values)) throw new \Exception("Arguments sources and values in ClassificationRow constructor have not same size.");
//
//        for ($i = 0; $i < count($sources); $i++) {
//            if (!$values[$i] == "-") $this->sources[$sources[$i]] = bcdiv($values[$i], "100");
//        }
//    }

    public function __construct(array $sources = array()) {
        $this->sources = $sources;
    }

    public function jsonSerialize() {
        return $this->sources;
    }

    /**
     * Get classification value of source from classification row.
     *
     * @param string $source name of group/source
     * @return string value
     */
    public function getSource($source) {
        if (!array_key_exists($source, $this->sources)) return null;
        return $this->sources[$source];
    }

    /**
     * Get names of top groups
     * @param int number num of values
     * @return array of groups names
     */
    public function getTopGroups($number) {
        $sources = array_merge(array(), $this->sources);
        uasort($sources, 'ValueComparator');

        $groups = array();
        foreach ($sources as $key => $val) {
            if ($number <= 0) break;
            $groups[] = $key;
            $number--;
        }
        return $groups;
    }

    /**
     * Get position of group in sorted classification row
     * @param string $group name
     * @return int position|-1
     */
    public function getGroupPosition($group) {
        $sources = array_merge(array(), $this->sources);
        uasort($sources, 'ValueComparator');

        $position = 1;
        foreach ($sources as $key => $val) {
            if ($key == $group) {
                break;
            }
            $position++;
        }
        if ($position > count($sources)) return -1;
        return $position;
    }

    /**
     * Get classification values of row.
     *
     * @return array fo values
     */
    public function getValues() {
        return $this->sources;
    }

    /**
     * Compute two classification row and return new computed row.
     *
     * @param ClassificationRow $otherRow other classification row to compute.
     * @return ClassificationRow new computed classification row
     */
    public function computeWithSameSource(ClassificationRow $otherRow) {
        $result = new ClassificationRow();
        $allSources = array_unique(array_merge(array_keys($this->sources), array_keys($otherRow->sources)));

        foreach ($allSources as $source) {
            if (array_key_exists($source, $this->sources) && array_key_exists($source, $otherRow->sources)) {
                $result->sources[$source] = bcmul($this->sources[$source], $otherRow->sources[$source]);
            }
        }
        $result->normalize();
        return $result;
    }

    /**
     * Compute two classification row and return new computed row.
     *
     * @param ClassificationRow $otherRow other classification row to compute.
     * @return ClassificationRow new computed classification row
     */
    public function computeWithNotSameSource(ClassificationRow $otherRow) {
        $result = new ClassificationRow();
        $allSources = array_unique(array_merge(array_keys($this->sources), array_keys($otherRow->sources)));

        foreach ($allSources as $source) {

            if (array_key_exists($source, $this->sources) || array_key_exists($source, $otherRow->sources)) {
                $val = "0";
                if (array_key_exists($source, $this->sources)) {
                    $val = bcadd($val, $this->sources[$source]);
                }
                if (array_key_exists($source, $otherRow->sources)) {
                    $val = bcadd($val, $otherRow->sources[$source]);
                }
                $result->sources[$source] = $val;
            }
        }
        $result->normalize();
        return $result;
    }

    /**
     * @return string
     */
    public function toString() {
        $tmp = "";
        $first = true;
        foreach ($this->sources as $key => $val) {
            if (!$first) {
                $tmp .= ", ";
            }
            else $first = false;
            $tmp .= $key . " => " . (doubleval($val) * 100);
        }
        return $tmp;
    }

    /**
     * Normalize values to interval [0,1] and sum of all values set to 1.
     */
    public function normalize() {
        $sum = "0";
        foreach ($this->sources as $val) {
            $sum = bcadd($sum, $val);
        }
        if (bccomp($sum, "0") == 0) return;
        foreach (array_keys($this->sources) as $key) {
            $this->sources[$key] = bcdiv($this->sources[$key], $sum);
        }
    }
}
