<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 29/07/2016
 * Time: 08:04
 */

namespace RSAKeyAnalysis\common;


class RomanNumber {
    //array of roman values
    public static $roman_values=array(
        'I' => 1, 'V' => 5,
        'X' => 10, 'L' => 50,
        'C' => 100, 'D' => 500,
        'M' => 1000,
    );
    //values that should evaluate as 0
    public static $roman_zero=array('N', 'nulla');
    //Regex - checking for valid Roman numerals
    public static $roman_regex='/^M{0,3}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$/';

    //Roman numeral validation function - is the string a valid Roman Number?
    static function IsRomanNumber($roman) {
        return preg_match(self::$roman_regex, $roman) > 0;
    }

    //Conversion: Roman Numeral to Integer
    static function Roman2Int ($roman) {
        //checking for zero values
        if (in_array($roman, self::$roman_zero)) {
            return 0;
        }
        //validating string
        if (!self::IsRomanNumber($roman)) {
            return false;
        }

        $values=self::$roman_values;
        $result = 0;
        //iterating through characters LTR
        for ($i = 0, $length = strlen($roman); $i < $length; $i++) {
            //getting value of current char
            $value = $values[$roman[$i]];
            //getting value of next char - null if there is no next char
            $nextvalue = !isset($roman[$i + 1]) ? null : $values[$roman[$i + 1]];
            //adding/subtracting value from result based on $nextvalue
            $result += (!is_null($nextvalue) && $nextvalue > $value) ? -$value : $value;
        }
        return $result;
    }

    /**
     * @param $integer
     * @return string
     */
    public static function Int2Roman($integer)
    {
        $table = array('M'=>1000, 'CM'=>900, 'D'=>500, 'CD'=>400, 'C'=>100, 'XC'=>90, 'L'=>50, 'XL'=>40, 'X'=>10, 'IX'=>9, 'V'=>5, 'IV'=>4, 'I'=>1);
        $return = '';
        while($integer > 0)
        {
            foreach($table as $rom=>$arb)
            {
                if($integer >= $arb)
                {
                    $integer -= $arb;
                    $return .= $rom;
                    break;
                }
            }
        }
        return $return;
    }

    /**
     * @param $a
     * @param $b
     * @return bool|int|mixed
     */
    public static function comparator($a, $b) {
        return RomanNumber::Roman2Int($a) - RomanNumber::Roman2Int($b);
    }
}