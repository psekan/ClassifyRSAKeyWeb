<?php

function ValueComparator($a, $b) {
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? 1 : -1;
}

/**
 * @param string $s1
 * @param string $s2
 * @return int
 */
function StringLengthComparator($s1, $s2) {
    $lengthDiff = strlen($s1) - strlen($s2);
    if ($lengthDiff != 0) return $lengthDiff;
    return strcmp($s1, $s2);
}

/**
 * @param array[] $a
 * @param array[] $b
 * @return int
 */
function SetSizeComparator($a, $b) {
    $ret = count($a) - count($b);
    if ($ret != 0) return $ret;
    return strcmp(implode(", ",$a), implode(", ",$b));
}

/**
 * Class GroupsComparator
 * @package RSAKeyAnalysis
 */
class GroupsComparator {
    private static $groupRepresentant = null;

    public static function setGroupsComparator($groupRepresentant) {
        self::$groupRepresentant = $groupRepresentant;
    }

    public static function compare($a, $b) {
        $a = json_decode($a, true);
        $b = json_decode($b, true);
        sort($a);
        sort($b);
        
        $ret = 0;
        if (self::$groupRepresentant == null) {
            $ret = count($a) - count($b);
        }
        else {
            $firstIndex = $secondIndex = -1;
            for ($i = 0; $i < count(self::$groupRepresentant); $i++) {
                if (in_array(self::$groupRepresentant[$i], $a) != false) $firstIndex = $i;
                if (in_array(self::$groupRepresentant[$i], $b) != false) $secondIndex = $i;
            }
            $ret = $firstIndex - $secondIndex;
        }
        if ($ret != 0) return $ret;
        return strcmp(implode(", ",$a), implode(", ",$b));
    }
}
