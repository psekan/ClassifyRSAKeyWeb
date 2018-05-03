<?php
namespace RSAKeyAnalysis;

require_once __DIR__ . "/../../Math/BigInteger.php";

use Math_BigInteger as BigInteger;

/**
 * @author Peter Sekan, peter.sekan@mail.muni.cz
 * @version 18.04.2016
 */
class RemainderFromDivisionTransformation extends Transformation {
    /**
     * Divider for modulo
     */
    private $divisor;

    public function __construct($from, $options) {
        parent::__construct($from, $options);
        $this->divisor = intval($this->getRequiredOption("divisor"));
    }

    public function transform(RSAKey $key) {
        /** @var BigInteger $remainder */
        list($quotient, $remainder) = $key->getPart($this->from)->divide(new BigInteger($this->divisor));
        $value = $remainder->toString();
        if (strpos($value, '.') !== false) {
            preg_match('/(.*)\..*/s', $value, $matches);
            return $matches[1];
        }
        return $value;
    }
}
