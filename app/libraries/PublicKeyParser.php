<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 09.07.2016
 * Time: 12:10
 */

namespace RSAKeyAnalysis;

use Math_BigInteger as BigInteger;
use OpenPGP;
use OpenPGP_Crypt_RSA;
use OpenPGP_Message;

set_include_path(__DIR__);

class PublicKeyParser
{
    const LENGTH_OF_IDENTIFICATION = 16;

    public static $maxUrlsClassifiable = null;

    /**
     * @param $filePath
     * @return RSAKey
     */
    public static function parseFromFile($filePath) {
        $fileContent = file_get_contents($filePath);
        return self::parseFromString($fileContent);
    }

    /**
     * @param $keyString
     * @return RSAKey
     */
    public static function parseFromString($keyString) {
        $key = self::parseFromUrl($keyString);
        if ($key === null) $key = self::tryOpenSSL($keyString);
        if ($key === null) $key = self::tryPGP($keyString);
        if ($key === null) $key = self::trySSH($keyString);
        return $key;
    }

    /**
     * @param $url
     * @return RSAKey
     */
    public static function parseFromUrl($url) {
        $url = trim($url);
        if (strpos($url, 'https://') !== 0) return null;
        if (self::$maxUrlsClassifiable != null) {
            if (self::$maxUrlsClassifiable <= 0) {
                return null;
            }
            self::$maxUrlsClassifiable--;
        }
        $url = str_replace("https://", "", $url);
        $posEnds = [strlen($url)];
        $delimiters = ["/"," "];
        foreach ($delimiters as $delimiter) {
            $newPos = strpos($url, $delimiter);
            if ($newPos !== false) {
                $posEnds[] = $newPos;
            }
        }
        $firstDel = min($posEnds);
        $url = substr($url, 0, $firstDel);
//        $firstDel = strpos($url, '/');
//        if ($firstDel !== false) {
//            $url = substr($url, 0, $firstDel);
//        }
        try {
            $g = stream_context_create(array("ssl" => array("capture_peer_cert" => true)));
            $r = @stream_socket_client("ssl://" . $url . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $g);
            if ($r === false) {
                return null;
            }
            $cont = stream_context_get_params($r);
            return self::tryOpenSSL($cont["options"]["ssl"]["peer_certificate"]);
        }
        catch (\Throwable $ex) {
            return null;
        }
    }

    /**
     * @param $armoredKeys
     * @return array
     */
    public static function parseMultiFromString($armoredKeys) {
        $keys = array();
        $pos = 0;
        $inputLength = strlen($armoredKeys);
        while (true) {
            $posOfSSH = strpos($armoredKeys, 'ssh-rsa ', $pos);
            $posOfUrl = strpos($armoredKeys, 'https://', $pos);
            $posOfArmor = strpos($armoredKeys, '-----BEGIN', $pos);

            $pos = $keyString = false;
            if ($posOfUrl !== false) {
                $pos = $posOfUrl;

                $posEnds = [$inputLength];
                $delimiters = ["\n","\t","?"," "];
                foreach ($delimiters as $delimiter) {
                    $newPos = strpos($armoredKeys, $delimiter, $pos);
                    if ($newPos !== false) {
                        $posEnds[] = $newPos;
                    }
                }
                $posEnd = min($posEnds);
                $keyString = substr($armoredKeys, $pos, $posEnd - $pos);
            }
            if ($posOfSSH !== false && ($pos === false || $pos > $posOfSSH)) {
                $pos = $posOfSSH;

                $posEnd = strpos($armoredKeys, "\n", $pos);
                if ($posEnd === false) {
                    $posEnd = $inputLength;
                }
                $keyString = substr($armoredKeys, $pos, $posEnd - $pos);
            }
            if ($posOfArmor !== false && ($pos === false || $pos > $posOfArmor)) {
                $pos = $posOfArmor;

                $posEnd = strpos($armoredKeys, '-----END', $pos);
                if ($posEnd === false) break;
                $posEnd += 8;
                $posEnd = strpos($armoredKeys, '-----', $posEnd);
                if ($posEnd === false) break;
                $posEnd += 5;
                $keyString = substr($armoredKeys, $pos, $posEnd - $pos);
            }
            if ($pos === false || $keyString === false) break;

            $key = self::parseFromString($keyString);
            $keys[] = [
                "text" => $keyString,
                "identification" => self::generateKeyIdentification($keyString),
                "key" => $key
            ];
            $pos = $posEnd;
        }
        return $keys;
    }

    public static function generateKeyIdentification($keyString) {
        if (strpos($keyString, 'https://') !== false) {
            $url = trim($keyString);
            $url = str_replace("https://", "", $url);
            $firstDel = strpos($url, '/');
            if ($firstDel !== false) {
                $url = substr($url, 0, $firstDel);
            }
            return $url;
        }
        else if (strpos(trim($keyString), 'ssh-rsa ') === 0) {
            $ssh = str_replace("ssh-rsa ", "", trim($keyString));
            return substr($ssh, 0, self::LENGTH_OF_IDENTIFICATION);
        }
        else {
            $blankLine = "\n\n";
            $keyString = str_replace("\r", "", $keyString);
            if (strpos($keyString, '-----BEGIN PGP PUBLIC KEY BLOCK-----') !== false) {
                $unArmor = OpenPGP::unarmor($keyString);
                if ($unArmor !== null) {
                    if (!is_object($unArmor)) $unArmor = OpenPGP_Message::parse($unArmor);
                    if ($unArmor instanceof OpenPGP_Message) $unArmor = $unArmor[0];
                    if (property_exists($unArmor, 'key')) return /*'PGP ID - ' .*/ strtoupper($unArmor->key_id);
                }
            }

            $newlinePos = strpos($keyString, $blankLine);
            if ($newlinePos !== false) {
                return substr($keyString, $newlinePos + strlen($blankLine), self::LENGTH_OF_IDENTIFICATION);
            }
            $pos = strpos($keyString, '-----BEGIN');
            if ($pos === false) return "NOT VALID ASCII ARMORED KEY";
            $pos = strpos($keyString, '-----', $pos + 8);
            if ($pos === false) return "NOT VALID ASCII ARMORED KEY";
            return substr($keyString, $pos + 5, self::LENGTH_OF_IDENTIFICATION);
        }
    }

    /**
     * @param $content
     * @return RSAKey
     */
    private static function tryOpenSSL($content) {
        $key = openssl_pkey_get_public($content);
        if ($key === false) {
            return null;
        }

        $details = openssl_pkey_get_details($key);
        if ($details === false || !array_key_exists("rsa", $details)) {
            return null;
        }

        $n = new BigInteger($details["rsa"]["n"], 256);
        $e = new BigInteger($details["rsa"]["e"], 256);
        return new RSAKey($n, $e);
    }

    /**
     * @param $content
     * @return RSAKey
     */
    private static function tryPGP($content) {
        $unArmor = OpenPGP::unarmor($content);
        if ($unArmor === null) return null;
        $key = OpenPGP_Crypt_RSA::convert_key($unArmor);
        if ($key === null) return null;
        
        return new RSAKey($key->modulus, $key->exponent);
    }

    /**
     * @param $content
     * @return RSAKey
     */
    private static function trySSH($content) {
        $content = trim($content);
        $exploded = explode(' ', $content);
        if (count($exploded) < 2 || $exploded[0] != "ssh-rsa") {
            return null;
        }
        $keyEncoded = bin2hex(base64_decode($exploded[1]));
        $mpints = self::parseMpints($keyEncoded);
        if ($mpints == null || count($mpints) != 3 || strtolower($mpints[0]) != '7373682d727361') {
            return null;
        }

        $n = new BigInteger($mpints[2], 16);
        $e = new BigInteger($mpints[1], 16);
        return new RSAKey($n, $e);
    }

    /**
     * @param $content
     * @return array|null
     */
    private static function parseMpints($content) {
        if (!ctype_xdigit($content)) return null;

        $mpints = array();
        while (strlen($content) > 0) {
            if (strlen($content) < 8) return null;
            $head = substr($content, 0, 8);
            $numOfBytes = intval(base_convert($head, 16, 10));

            if (strlen($content) < (8 + $numOfBytes*2)) return null;
            $mpints[] = substr($content, 8, $numOfBytes*2);
            $content = substr($content, 8 + $numOfBytes*2);
        }
        return $mpints;
    }
}