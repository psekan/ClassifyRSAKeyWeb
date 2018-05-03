<?php
namespace RSAKeyAnalysis;

require_once __DIR__ . "/exception/TransformationNotFoundException.php";
require_once __DIR__ . "/exception/WrongOptionsFormatException.php";
require_once __DIR__ . "/exception/WrongTransformationFormatException.php";

/**
 * @author Peter Sekan, peter.sekan@mail.muni.cz
 * @version 18.04.2016
 */
abstract class Transformation {
    /**
     * Part of rsa key which is transformed to identification part
     */
    protected $from;

    /**
     * Json object of transformation's options
     */
    protected $options;

    /**
     * JSONObject contains options for transformation
     * @param string $from part of rsa key which is transformed to identification part
     * @param array $options JSONObject contains options for transformation
     */
    public function __construct($from, $options) {
        $this->from = $from;
        $this->options = $options;
    }

    protected function getRequiredOption($option) {

        if (!array_key_exists($option, $this->options)) {
            throw new WrongOptionsFormatException("Options for " . get_called_class() . " does not contains parameter \"" . $option . "\".");
        }
        return $this->options[$option];
    }

    /**
     * Transform part of key to identification part
     * @param RSAKey $key rsa key
     * @return string $identification part
     */
    public abstract function transform(RSAKey $key);

    /**
     * Create transformation object from identification part json object
     * @param array $identificationPart json object
     * @return Transformation
     * @throws WrongTransformationFormatException
     * @throws TransformationNotFoundException
     */
    public static function createFromIdentificationPart($identificationPart) {
        if (!array_key_exists("transformationId", $identificationPart)) {
            throw new WrongTransformationFormatException("Transformation does not contain parameter 'transformationId'");
        }
        if (!array_key_exists("transform", $identificationPart)) {
            throw new WrongTransformationFormatException("Transformation does not contain parameter 'transform'");
        }
        if (!array_key_exists("options", $identificationPart)) {
            throw new WrongTransformationFormatException("Transformation does not contain parameter 'options'");
        }

        $transformationId = $identificationPart["transformationId"];
        $transform = strtolower($identificationPart["transform"]);

        if (preg_match("/[^a-zA-Z0-9]/", $transformationId)) {
            throw new WrongTransformationFormatException("Transformation parameter 'transformationId' is empty or contains not allowed characters");
        }

        switch ($transform) {
            case "n": 
            case "e": 
            case "d": 
            case "p": 
            case "q": 
            case "phi": 
            case "nblen":  
            case "pblen":  
            case "qblen": 
                $from = strtoupper($transform); 
                break;
            default: throw new WrongTransformationFormatException("Transformation parameter 'transform' is not one of {N,E,D,P,Q,PHI,NBLEN,PBLEN,QBLEN}");
        }

        try {
            $classFile = __DIR__ . "/" . $transformationId . "Transformation.php";
            require_once $classFile;
            $transformationClass = "\\RSAKeyAnalysis\\" . $transformationId . "Transformation";
            return new $transformationClass($from, $identificationPart["options"]);
        }
        catch (\Exception $ex) {
            throw new TransformationNotFoundException("Cannot create transformation with id '" . $transformationId . "'.", $ex);
        }
    }
}
