<?php

namespace Eiep;

use \DateTime;

/**
 * Class DetailRecord
 *
 * @package Eiep
 */
abstract class DetailRecord
{
    const NULL_COLUMN = 'null';
    const DECIMAL_PLACES = 2;

    const READING_TYPE_FINAL = 'F';
    const READING_TYPE_ESTIMATE = 'E';

    const FLOW_DIRECTION_INJECT = 'I';
    const FLOW_DIRECTION_EXTRACT = 'X';

    /**
     * @var string
     */
    protected $icpIdentifier;

    /**
     * @var string
     */
    protected $streamIdentifier;

    /**
     * @var string
     */
    protected $readingType;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var int
     */
    protected $tradingPeriod;

    /**
     * @var float
     */
    protected $activeEnergy;

    /**
     * @var float
     */
    protected $reactiveEnergy;

    /**
     * @var float
     */
    protected $apparentEnergy;

    /**
     * @var string
     */
    protected $flowDirection;

    /**
     * @var string
     */
    protected $streamType;

    /**
     * DetailRecord constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->date = new DateTime();
        $this->date->setTime(0, 0, 0);
    }

    /**
     * @param array $data
     *
     * @return DetailRecord
     * @throws \Exception
     */
    abstract static function createFromRow(array $data);

    /**
     * @return string
     */
    public function getIcpIdentifier(): string
    {
        return $this->icpIdentifier;
    }

    /**
     * @param string $icpIdentifier
     *
     * @return DetailRecord
     */
    public function setIcpIdentifier(string $icpIdentifier): DetailRecord
    {
        $this->icpIdentifier = $this->removeWhitespace($icpIdentifier);

        return $this;
    }

    /**
     * @return string
     */
    public function getStreamIdentifier(): ?string
    {
        return $this->streamIdentifier;
    }

    /**
     * @param string $streamIdentifier
     *
     * @return DetailRecord
     */
    public function setStreamIdentifier(string $streamIdentifier): DetailRecord
    {
        $this->streamIdentifier = $this->removeWhitespace($streamIdentifier);

        return $this;
    }

    /**
     * @return string
     */
    public function getReadingType(): string
    {
        return $this->readingType;
    }

    /**
     * @param string $readingType
     *
     * @return DetailRecord
     * @throws \Exception
     */
    public function setReadingType(string $readingType): DetailRecord
    {
        if (!in_array($readingType, [self::READING_TYPE_ESTIMATE, self::READING_TYPE_FINAL])) {
            throw new \Exception("Invalid reading type");
        }

        $this->readingType = $readingType;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     *
     * @return DetailRecord
     */
    public function setDate(DateTime $date): DetailRecord
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return int
     */
    public function getTradingPeriod(): int
    {
        return $this->tradingPeriod;
    }

    /**
     * @param int $tradingPeriod
     *
     * @return DetailRecord
     * @throws \Exception
     */
    public function setTradingPeriod(int $tradingPeriod): DetailRecord
    {
        if ($tradingPeriod < 0 || $tradingPeriod > 50) {
            throw new \Exception("Invalid trading period {$tradingPeriod}, expected 1 - 50");
        }

        $this->tradingPeriod = $tradingPeriod;

        return $this;
    }

    /**
     * @return float
     */
    public function getActiveEnergy(): ?float
    {
        return $this->activeEnergy;
    }

    /**
     * @param float|null $activeEnergy
     *
     * @return DetailRecord
     */
    public function setActiveEnergy(?float $activeEnergy): DetailRecord
    {
        $this->activeEnergy = $activeEnergy;

        return $this;
    }

    /**
     * @return float
     */
    public function getReactiveEnergy(): ?float
    {
        return $this->reactiveEnergy;
    }

    /**
     * @param float|null $reactiveEnergy
     *
     * @return DetailRecord
     */
    public function setReactiveEnergy(?float $reactiveEnergy): DetailRecord
    {
        $this->reactiveEnergy = $reactiveEnergy;

        return $this;
    }

    /**
     * @return float
     */
    public function getApparentEnergy(): ?float
    {
        return $this->apparentEnergy;
    }

    /**
     * @param float|null $apparentEnergy
     *
     * @return DetailRecord
     */
    public function setApparentEnergy(?float $apparentEnergy): DetailRecord
    {
        $this->apparentEnergy = $apparentEnergy;

        return $this;
    }

    /**
     * @return string
     */
    public function getFlowDirection(): string
    {
        return $this->flowDirection;
    }

    /**
     * @param string $flowDirection
     *
     * @return DetailRecord
     * @throws \Exception
     */
    public function setFlowDirection(string $flowDirection): DetailRecord
    {
        if (!in_array($flowDirection, [self::FLOW_DIRECTION_INJECT, self::FLOW_DIRECTION_EXTRACT])) {
            throw new \Exception("Invalid energy flow direction");
        }

        $this->flowDirection = $flowDirection;

        return $this;
    }

    /**
     * @return string
     */
    public function getStreamType(): ?string
    {
        return $this->streamType;
    }

    /**
     * @param string|null $streamType
     *
     * @return DetailRecord
     */
    public function setStreamType(?string $streamType): DetailRecord
    {
        $this->streamType = $streamType;

        return $this;
    }

    /**
     * Format the record for writing to the CSV file
     *
     * @return array
     */
    abstract function toArray(): array;

    /**
     * Format numbers to the 12.2 specification
     *
     * @param float|null $number
     *
     * @return string
     */
    protected function formatNumber(?float $number)
    {
        if (is_null($number)) {
            return self::NULL_COLUMN;
        }

        return number_format($number, self::DECIMAL_PLACES);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    protected function removeWhitespace(string $text)
    {
        return preg_replace('/\s+/', '', $text);
    }
}
