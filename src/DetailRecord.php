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
     */
    public function setIcpIdentifier(string $icpIdentifier): void
    {
        $this->icpIdentifier = $this->removeWhitespace($icpIdentifier);
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
     */
    public function setStreamIdentifier(string $streamIdentifier): void
    {
        $this->streamIdentifier = $this->removeWhitespace($streamIdentifier);
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
     */
    public function setReadingType(string $readingType): void
    {
        $this->readingType = $readingType;
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
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
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
     * @throws \Exception
     */
    public function setTradingPeriod(int $tradingPeriod): void
    {
        if ($tradingPeriod < 0 || $tradingPeriod > 50) {
            throw new \Exception("Invalid trading period {$tradingPeriod}, expected 1 - 50");
        }

        $this->tradingPeriod = $tradingPeriod;
    }

    /**
     * @return float
     */
    public function getActiveEnergy(): ?float
    {
        return $this->activeEnergy;
    }

    /**
     * @param float $activeEnergy
     */
    public function setActiveEnergy(?float $activeEnergy): void
    {
        $this->activeEnergy = $activeEnergy;
    }

    /**
     * @return float
     */
    public function getReactiveEnergy(): ?float
    {
        return $this->reactiveEnergy;
    }

    /**
     * @param float $reactiveEnergy
     */
    public function setReactiveEnergy(?float $reactiveEnergy): void
    {
        $this->reactiveEnergy = $reactiveEnergy;
    }

    /**
     * @return float
     */
    public function getApparentEnergy(): ?float
    {
        return $this->apparentEnergy;
    }

    /**
     * @param float $apparentEnergy
     */
    public function setApparentEnergy(?float $apparentEnergy): void
    {
        $this->apparentEnergy = $apparentEnergy;
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
     */
    public function setFlowDirection(string $flowDirection): void
    {
        $this->flowDirection = $flowDirection;
    }

    /**
     * @return string
     */
    public function getStreamType(): ?string
    {
        return $this->streamType;
    }

    /**
     * @param string $streamType
     */
    public function setStreamType(?string $streamType): void
    {
        $this->streamType = $streamType;
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
