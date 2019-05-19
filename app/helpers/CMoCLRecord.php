<?php
/**
 * Created by PhpStorm.
 * User: peter
 * Date: 28/4/2019
 * Time: 11:12 AM
 */

namespace ClassifyRSA\Helpers;


use DateTime;
use InvalidArgumentException;
use JsonSerializable;

class CMoCLRecord implements JsonSerializable
{
    const PERIOD_DAY = 'day';
    const PERIOD_MONTH = 'month';
    const PERIOD_WEEK = 'week';
    const PERIOD_OCCASIONAL = 'occasional';

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $period;

    /**
     * @var string
     */
    private $date;

    /**
     * @var string
     */
    private $estimation;

    /**
     * CMoCLRecord constructor.
     * @param string $source
     * @param int $period
     * @param string $date
     * @param string $estimation
     */
    public function __construct($source, $period, $date, $estimation)
    {
        $this->source = $source;
        $this->period = $period;
        $this->date = $date;
        $this->estimation = $estimation;
        if (!$this->isValid()) {
            throw new InvalidArgumentException('CMoCL Record was initialized with invalid data.');
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'source' => $this->source,
            'period' => $this->period,
            'date' => $this->date,
            'estimation' => json_decode($this->estimation, true)
        );
    }

    /**
     * @param string|array $data
     * @return CMoCLRecord|null
     */
    public static function jsonDeserialize($data) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            return null;
        }
        if (!array_key_exists('source', $data) ||
            !array_key_exists('period', $data) ||
            !array_key_exists('date', $data) ||
            !array_key_exists('estimation', $data)) {
            return null;
        }
        if (!is_string($data['estimation'])) {
            $data['estimation'] = json_encode($data['estimation']);
        }
        try {
            return new CMoCLRecord($data['source'], $data['period'], $data['date'], $data['estimation']);
        }
        catch (InvalidArgumentException $ex) {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function isValid() {
        return $this->isEstimationValid() && $this->isDateValid() && self::isPeriodValid($this->period);
    }

    /**
     * @return bool
     */
    protected function isDateValid() {
        return DateTime::createFromFormat('Y-m-d', $this->date) !== FALSE;
    }

    /**
     * @param $period
     * @return bool
     */
    public static function isPeriodValid($period) {
        return in_array($period, array(self::PERIOD_DAY, self::PERIOD_MONTH, self::PERIOD_WEEK, self::PERIOD_OCCASIONAL));
    }

    /**
     * @return bool
     */
    protected function isEstimationValid() {
        $decoded = json_decode($this->estimation, true);
        if (!array_key_exists('probability', $decoded) ||
            !array_key_exists('groups', $decoded) ||
            !array_key_exists('frequencies', $decoded)) {
            return false;
        }
        if (empty($decoded['probability']) ||
            empty($decoded['groups']) ||
            empty($decoded['frequencies'])) {
            return false;
        }
        foreach ($decoded['probability'] as $group => $val) {
            if (!array_key_exists($group, $decoded['groups'])){
                return false;
            }
        }
        return true;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getEstimation()
    {
        return $this->estimation;
    }
}
