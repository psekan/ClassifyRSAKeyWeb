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

require_once __DIR__ . '/openpgp.php';
require_once __DIR__ . '/openpgp_crypt_rsa.php';
require_once __DIR__ . '/common/RSAKey.php';
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
        $firstDel = strpos($url, '/');
        if ($firstDel !== false) {
            $url = substr($url, 0, $firstDel);
        }
        $g = stream_context_create(array("ssl" => array("capture_peer_cert" => true)));
        $r = stream_socket_client("ssl://" . $url . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $g);
        if ($r === false) {
            return null;
        }
        $cont = stream_context_get_params($r);
        return self::tryOpenSSL($cont["options"]["ssl"]["peer_certificate"]);
    }

    /**
     * @param $armoredKeys
     * @return RSAKey[]
     */
    public static function parseMultiFromString($armoredKeys) {
        $keys = array();
        $pos = 0;
        while (true) {
            $posOfSSH = strpos($armoredKeys, 'ssh-rsa ', $pos);
            $posOfUrl = strpos($armoredKeys, 'https://', $pos);
            $posOfArmor = strpos($armoredKeys, '-----BEGIN', $pos);

            $pos = $armor = false;
            if ($pos === false || ($posOfUrl !== false && $pos > $posOfUrl)) $pos = $posOfUrl;
            if ($pos === false || ($posOfSSH !== false && $pos > $posOfSSH)) $pos = $posOfSSH;
            if ($pos === false || ($posOfArmor !== false && $pos > $posOfArmor)) {
                $pos = $posOfArmor;
                $armor = true;
            }
            if ($pos === false) break;

            if ($armor) {
                $posEnd = strpos($armoredKeys, '-----END', $pos);
                if ($posEnd === false) break;
                $posEnd += 8;
                $posEnd = strpos($armoredKeys, '-----', $posEnd);
                if ($posEnd === false) break;
                $posEnd += 5;
                $keyString = substr($armoredKeys, $pos, $posEnd - $pos);
            }
            else {
                $posEnd = strpos($armoredKeys, "\n", $pos);
                if ($posEnd === false) {
                    $posEnd = strlen($armoredKeys);
                }
                $keyString = substr($armoredKeys, $pos, $posEnd - $pos);
            }
            $key = self::parseFromString($keyString);
            $keys[] = array(
                "text" => $keyString,
                "identification" => self::generateKeyIdentification($keyString),
                "key" => $key,
            );
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