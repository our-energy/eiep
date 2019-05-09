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
    protected $numRecords = 0;

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

    /**
     * @var resource
     */
    protected $stream;

    /**
     * @return array
     */
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
     *
     * @return $this
     */
    public function setSender(string $sender)
    {
        $this->sender = $sender;

        return $this;
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
     *
     * @return $this
     */
    public function setOnBehalfOf(string $onBehalfOf)
    {
        $this->onBehalfOf = $onBehalfOf;

        return $this;
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
     *
     * @return $this
     */
    public function setRecipient(string $recipient)
    {
        $this->recipient = $recipient;

        return $this;
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
     *
     * @return $this
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;

        return $this;
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
     *
     * @return $this
     */
    public function setNumRecords(int $numRecords)
    {
        $this->numRecords = $numRecords;

        return $this;
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
     *
     * @return $this
     */
    public function setReportDate(DateTime $dateTime)
    {
        $this->reportDateTime = $dateTime;

        $this->reportDate = $dateTime->format(self::DATE_FORMAT);
        $this->reportMonth = $dateTime->format("Ym");
        $this->reportTime = $dateTime->format("H:i:s");

        return $this;
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
     * @return $this
     * @throws \Exception
     */
    private function createReadStream(string $fileName, callable $callback)
    {
        $stream = fopen($fileName, 'r');

        $this->readStream($stream, $callback);

        return $this;
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
     * @param $stream
     *
     * @throws \Exception
     */
    public function readHeaderFromStream($stream): void
    {
        if (!is_resource($stream)) {
            throw new \Exception("Stream is not a valid resource");
        }

        $reader = Reader::createFromStream($stream);

        $header = $reader->fetchOne();

        $this->validateHeader($header);
    }

    /**
     * @param string $fileName
     *
     * @throws \Exception
     */
    public function readHeaderFromFile(string $fileName): void
    {
        if (!file_exists($fileName)) {
            throw new \Exception("File not found: {$fileName}");

        }
        $stream = fopen($fileName, 'r');

        $this->readHeaderFromStream($stream);

        $this->stream = $stream;
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
