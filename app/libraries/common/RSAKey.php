<?php
namespace RSAKeyAnalysis;

require_once __DIR__ . "/../Math/BigInteger.php";

use Math_BigInteger as BigInteger;

/**
 * @author David Formanek
 * @author Peter Sekan, peter.sekan@mail.muni.cz
 */
class RSAKey implements \JsonSerializable {
    /**
     * Parts of rsa key
     */
    public $PART = array(
        "N", "E", "D", "P", "Q", "PHI", "NBLEN", "PBLEN", "QBLEN"
    );

    const PRIME_CERTAINITY = 40;

    /**
     * @var BigInteger
     */
    private $exponent = null;

    /**
     * @var BigInteger
     */
    private $modulus = null;

    /**
     * @var BigInteger
     */
    private $p = null;

    /**
     * @var BigInteger
     */
    private $q = null;

    /**
     * @var int
     */
    private $time = 0;

    /**
     * @var bool
     */
    private $checkedValidity = false;

    /**
     * @var bool
     */
    private $validKey = true;

    public function __construct(BigInteger $modulus = null, BigInteger $exponent = null) {
        $this->modulus = $modulus;
        $this->exponent = $exponent;
    }

    public function jsonSerialize()
    {
        return [
            'modulusBitLen' => strlen($this->getModulus()->toBits()),
            'exponent' => intval($this->getExponent()->toString())
        ];
    }

    /**
     * @return BigInteger
     */
    public function getExponent() {
        return $this->exponent;
    }

    public function setExponent(BigInteger $exponent) {
        $this->exponent = $exponent;
    }

    /**
     * @return BigInteger
     */
    public function getModulus() {
        return $this->modulus;
    }

    public function setModulus(BigInteger $modulus) {
        $this->modulus = $modulus;
    }

    /**
     * @return BigInteger
     */
    public function getP() {
        return $this->p;
    }

    public function setP(BigInteger $p) {
        $this->p = $p;
    }

    /**
     * @return BigInteger
     */
    public function getQ() {
        return $this->q;
    }

    public function setQ(BigInteger $q) {
        $this->q = $q;
    }

    /**
     * @return int
     */
    public function getTime() {
        return $this->time;
    }

    public function setTime($time) {
        $this->time = $time;
    }

    /**
     * @return boolean
     */
    public function isValidKey() {
        return $this->isValid(false);
    }

    public function isValid($writeInfo) {
        if ($this->checkedValidity)
            return $this->validKey;

        $isValid = true;
        if (!$this->p->isPrime(self::PRIME_CERTAINITY)) {
            $isValid = false;
            if ($writeInfo) {
                echo("p " . $this->p->toString() . " is not a prime");
            }
        }
        if (!$this->q->isPrime(self::PRIME_CERTAINITY)) {
            $isValid = false;
            if ($writeInfo) {
                echo("q " . $this->q->toString() . " is not a prime");
            }
        }
        if (!$this->p->multiply($this->q)->equals($this->modulus)) {
            $isValid = false;
            if ($writeInfo) {
                echo("Modulus " . $this->modulus->toString() . " has not factors p a q");
            }
        }
        $phi = $this->getPhi();
        if (!$phi->gcd($this->exponent)->equals(new BigInteger("1"))) {
            $isValid = false;
            if ($writeInfo) {
                echo("Exponent " . $this->exponent->toString()
                        . " is not coprime to phi of " . $this->modulus->toString());
            }
        }
        $this->checkedValidity = true;
        $this->validKey = $isValid;
        return $isValid;
    }

    public function getPrimeDifference() {
        return $this->p->subtract($this->q)->abs();
    }

    /**
     * @return false|BigInteger
     */
    public function getPrivateExponent() {
        $phi = $this->getPhi();
        return $this->exponent->modInverse($phi);
    }

    /**
     * @return BigInteger
     */
    public function getPhi() {
        return $this->modulus->subtract($this->p)->subtract($this->q)->add(new BigInteger("1"));
    }

    /**
     * @param $part
     * @return BigInteger
     */
    public function getPart($part) {
        switch ($part) {
            case "N":
                return $this->getModulus();
            case "E":
                return $this->getExponent();
            case "D":
                return $this->getPrivateExponent();
            case "P":
                return $this->getP();
            case "Q":
                return $this->getQ();
            case "PHI":
                return $this->getPhi();
            case "NBLEN":
                return new BigInteger(strlen($this->getModulus()->toBits()));
            case "PBLEN":
                return new BigInteger(strlen($this->getP()->toBits()));
            case "QBLEN":
                return new BigInteger(strlen($this->getQ()->toBits()));
            default:
                return null;
        }
    }
}
