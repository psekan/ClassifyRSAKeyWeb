<?php

use ClassifyRSA\Helpers\CMoCLRecord;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

/**
 * Class CMoCLRecordTest extends TestCase
 * @testCase
 */
class CMoCLRecordTest extends TestCase
{
    public function testConstructor() {
        $record = new CMoCLRecord("ct", CMoCLRecord::PERIOD_DAY, "2019-01-01", '{"probability":{"Group 1":"0.09836608608022958","Group 2":"0.00163391391911042"},"groups":{"Group 1":["Bouncy Castle 1.54","Crypto++ <=5.6.5","Microsoft CNG & .NET & CryptoAPI"],"Group 2":["OpenSSL <=1.1.0e"]},"frequencies":{"0|1|1|100100":14961,"0|1|1|100101":14318}}');
        Assert::true(is_string($record->getEstimation()));
        Assert::true($record->isValid());
    }

    public function testDeserializeFromArray() {
        $record = CMoCLRecord::jsonDeserialize(array(
            "source" => "ct",
            "period" => CMoCLRecord::PERIOD_DAY,
            "date" => "2019-01-01",
            "estimation" => '{"probability":{"Group 1":"0.09836608608022958","Group 2":"0.00163391391911042"},"groups":{"Group 1":["Bouncy Castle 1.54","Crypto++ <=5.6.5","Microsoft CNG & .NET & CryptoAPI"],"Group 2":["OpenSSL <=1.1.0e"]},"frequencies":{"0|1|1|100100":14961,"0|1|1|100101":14318}}'
        ));
        Assert::notSame($record, null);
        Assert::true(is_string($record->getEstimation()));
    }

    public function testDeserializeFromString() {
        $record = CMoCLRecord::jsonDeserialize(array(
            "source" => "ct",
            "period" => CMoCLRecord::PERIOD_DAY,
            "date" => "2019-01-01",
            "estimation" => '{"probability":{"Group 1":"0.09836608608022958","Group 2":"0.00163391391911042"},"groups":{"Group 1":["Bouncy Castle 1.54","Crypto++ <=5.6.5","Microsoft CNG & .NET & CryptoAPI"],"Group 2":["OpenSSL <=1.1.0e"]},"frequencies":{"0|1|1|100100":14961,"0|1|1|100101":14318}}'
        ));
        $json = json_encode($record->jsonSerialize());
        var_dump($json);
        $record = CMoCLRecord::jsonDeserialize($json);
        Assert::notSame($record, null);
        Assert::true(is_string($record->getEstimation()));
    }
}

(new CMoCLRecordTest())->run();
