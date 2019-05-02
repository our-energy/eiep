<?php

namespace Eiep;

use DateTime;
use League\Csv\Reader;
use League\Csv\Writer;

/**
 * Trait
 *
 * @package Eiep
 */
trait Protocol
{
    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $sender;

    /**
     * @var string
     */
    protected $onBehalfOf;

    /**
     * @var string
     */
    protected $recipient;

    /**
     * @var DateTime
     */
    protected $reportDateTime;

    /**
     * @var string
     */
    protected $reportDate;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var int
     */
    protected $numRecords;

    /**
     * @var string
     */
    protected $reportTime;

    /**
     * @var string
     */
    protected $reportMonth;

    /**
     * @var array
     */
    protected $header;

    abstract function getHeader(): array;

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     */
    public function setSender(string $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @return string
     */
    public function getOnBehalfOf(): string
    {
        return $this->onBehalfOf;
    }

    /**
     * @param string $onBehalfOf
     */
    public function setOnBehalfOf(string $onBehalfOf): void
    {
        $this->onBehalfOf = $onBehalfOf;
    }

    /**
     * @return string
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    /**
     * @param string $recipient
     */
    public function setRecipient(string $recipient): void
    {
        $this->recipient = $recipient;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return int
     */
    public function getNumRecords(): int
    {
        return $this->numRecords;
    }

    /**
     * @param int $numRecords
     */
    public function setNumRecords(int $numRecords): void
    {
        $this->numRecords = $numRecords;
    }


    /**
     * @return string
     */
    public function getReportDate(): string
    {
        return $this->reportDateTime->format(self::DATE_FORMAT);
    }

    /**
     * @param DateTime $dateTime
     */
    public function setReportDate(DateTime $dateTime): void
    {
        $this->reportDateTime = $dateTime;

        $this->reportDate = $dateTime->format(self::DATE_FORMAT);
        $this->reportMonth = $dateTime->format("Ym");
        $this->reportTime = $dateTime->format("H:i:s");
    }

    /**
     * @return string
     */
    public function getReportTime(): string
    {
        return $this->reportTime;
    }

    /**
     * @return string
     */
    public function getReportMonth(): string
    {
        return $this->reportMonth;
    }

    /**
     * @param string $fileName
     * @param callable $callback
     *
     * @throws \Exception
     */
    private function createReadStream(string $fileName, callable $callback): void
    {
        $stream = fopen($fileName, 'r');

        $this->readStream($stream, $callback);
    }

    /**
     * @param mixed $stream
     * @param callable $callback
     *
     * @throws \Exception
     */
    private function readStream($stream, callable $callback): void
    {
        if (!is_resource($stream)) {
            throw new \Exception("Stream is not a valid resource");
        }

        $reader = Reader::createFromStream($stream);

        foreach ($reader as $index => $row) {

            // Clean up the data
            $row = array_map(function($column) {
                $column = trim($column);

                // Convert string nulls to actual
                if (strtolower($column) === DetailRecord::NULL_COLUMN) {
                    $column = null;
                }

                return $column;
            }, $row);

            // HDR record
            if ($index === 0) {
                if (!$this->validateHeader($row)) {
                    throw new \Exception("HDR record is in an invalid format");
                }

                continue;
            }

            $callback($row);
        }
    }

    /**
     * @param string $fileName
     *
     * @return Writer
     * @throws \League\Csv\CannotInsertRecord
     * @throws \League\Csv\Exception
     */
    public function createWriter(string $fileName): Writer
    {
        $stream = fopen($fileName, 'w');

        $writer = Writer::createFromStream($stream);

        // Write the HDR
        $writer->insertOne($this->getHeader());

        return $writer;
    }

    /**
     * @param array $header
     *
     * @return bool
     */
    abstract function validateHeader(array $header): bool;
}
