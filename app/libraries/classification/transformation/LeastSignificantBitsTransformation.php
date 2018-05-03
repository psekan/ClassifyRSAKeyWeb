<?php
namespace RSAKeyAnalysis;

/**
 * @author Peter Sekan, peter.sekan@mail.muni.cz
 * @version 18.04.2016
 */
class LeastSignificantBitsTransformation extends Transformation {
    /**
     * Number of bits to extract from part of rsa key
     */
    private $bits;

    /**
     * Number of bits to skip from part of rsa key
     */
    private $skip = 1;

    public function __construct($from, $options) {
        parent::__construct($from, $options);
        if (array_key_exists("skip", $options)) {
            $this->skip = intval($options["skip"]);
        }
        $this->bits = intval($this->getRequiredOption("bits"));
    }

    public function transform(RSAKey $key) {
        $partBinary = $key->getPart($this->from)->toBits();
        return substr($partBinary, strlen($partBinary) - $this->bits - $this->skip, $this->bits);
    }
}
