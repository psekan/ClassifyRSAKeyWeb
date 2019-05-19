<?php

use ClassifyRSA\DatabaseModel;
use ClassifyRSA\Helpers\CMoCLRecord;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

/** @var Container $container */
$container = require __DIR__ . '/bootstrap.php';

/** @var DatabaseModel $database */
$database = $container->getService('database');

/**
 * Class DatabaseTest extends TestCase
 * @testCase
 */
class DatabaseTest extends TestCase
{
    const ESTIMATION_DATA = '{"probability":{"Group 1":"0.09836608608022958","Group 2":"0.00163391391911042"},"groups":{"Group 1":["Bouncy Castle 1.54","Crypto++ <=5.6.5","Microsoft CNG & .NET & CryptoAPI"],"Group 2":["OpenSSL <=1.1.0e"]},"frequencies":{"0|1|1|100100":14961,"0|1|1|100101":14318}}';

    /**
     * @var DatabaseModel
     */
    private $database;

    /**
     * DatabaseTest constructor.
     * @param DatabaseModel $database
     */
    public function __construct(DatabaseModel $database)
    {
        $this->database = $database;
    }

    public function testInsertCMoCLRecord() {
        $record = new CMoCLRecord("ct", CMoCLRecord::PERIOD_DAY, "2019-01-01", self::ESTIMATION_DATA);
        $this->database->insertCMoCLRecord($record);

        $dates = $this->database->getCMoCLRecord("ct", CMoCLRecord::PERIOD_DAY, "2019-01-01");
        Assert::notSame($dates, null);
    }

    public function testSources() {
        $sources = $this->database->getCMoCLSources();
        if (!in_array("ct", $sources)){
            $record = new CMoCLRecord("ct", CMoCLRecord::PERIOD_DAY, "2019-01-01", self::ESTIMATION_DATA);
            $this->database->insertCMoCLRecord($record);
        }
        $sources = $this->database->getCMoCLSources();
        Assert::true(in_array("ct", $sources));
    }

    public function testDates() {
        $dates = $this->database->getCMoCLAvailableDates("ct", CMoCLRecord::PERIOD_DAY);
        if (!in_array("2019-01-01", $dates)){
            $record = new CMoCLRecord("ct", CMoCLRecord::PERIOD_DAY, "2019-01-01", self::ESTIMATION_DATA);
            $this->database->insertCMoCLRecord($record);
        }
        $dates = $this->database->getCMoCLAvailableDates("ct", CMoCLRecord::PERIOD_DAY);
        Assert::true(in_array("2019-01-01", $dates));
    }

    public function testFind() {
        $record = new CMoCLRecord("ct", CMoCLRecord::PERIOD_DAY, "2019-02-01", self::ESTIMATION_DATA);
        $this->database->insertCMoCLRecord($record);

        $record = new CMoCLRecord("ct", CMoCLRecord::PERIOD_DAY, "2019-02-03", self::ESTIMATION_DATA);
        $this->database->insertCMoCLRecord($record);

        $A = $this->database->getCMoCLRecordsFromTo("ct", CMoCLRecord::PERIOD_DAY, "2019-02-01", "2019-02-02");
        $B = $this->database->getCMoCLRecordsFromTo("ct", CMoCLRecord::PERIOD_DAY, "2019-02-03", "2019-02-03");
        $C = $this->database->getCMoCLRecordsFromTo("ct", CMoCLRecord::PERIOD_DAY, "2019-02-01", "2019-02-03");
        Assert::true(count($A) <= count($C));
        Assert::true(count($B) <= count($C));
        Assert::true(count($A) + count($B) == count($C));
    }
}

(new DatabaseTest($database))->run();

