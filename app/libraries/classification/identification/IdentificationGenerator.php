<?php
namespace RSAKeyAnalysis;

/**
 * @author Peter Sekan, peter.sekan@mail.muni.cz
 * @version 20.04.2016
 */
class IdentificationGenerator {
    /**
     * Array of transformations
     */
    private $transformations;

    /**
     * Create generator
     * @param array $transformations list of used transformations
     */
    public function __construct($transformations) {
        $this->transformations = $transformations;
    }

    /**
     * Generate identification by this generator
     * @param RSAKey $key rsa key
     * @return string identification
     */
    public function generationIdentification(RSAKey $key) {
        $identifications = array();
        foreach ($this->transformations as $transformation) {
            $identifications[] = $transformation->transform($key);
        }
        return implode("|", $identifications);
    }
}
