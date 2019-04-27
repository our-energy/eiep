<?php

namespace Eiep\Eiep13a;

use Eiep\Protocol;
use Eiep\EiepInterface;
use Eiep\Eiep13a\DetailRecord;
use League\Csv\Reader;
use League\Csv\Writer;
use DateTime;

/**
 * Class Report
 *
 * @package Eiep\Eiep13a
 */
class Report extends Protocol implements EiepInterface
{
    const FILE_TYPE = 'ICPCONS';
    const SUPPORTED_VERSIONS = ['1.1'];
    const DEFAULT_VERSION = '1.1';
    const NUM_HEADER_COLUMNS = 11;

    /**
     * @var DateTime
     */
    private $reportStartDate;

    /**
     * @var DateTime
     */
    private $reportEndDate;

    /**
     * @return array
     */
    function getHeader(): array
    {
        return [
            'HDR',
            self::FILE_TYPE,
            $this->version,
            substr($this->sender, 0, 4),
            substr($this->onBehalfOf, 0, 4),
            substr($this->recipient, 0, 4),
            $this->reportDate,
            substr($this->identifier, 0, 15),
            str_pad($this->numRecords, 8, "0", STR_PAD_LEFT),
            $this->reportStartDate->format("d/m/Y"),
            $this->reportEndDate->format("d/m/Y")
        ];
    }

    function validateHeader(array $header): bool
    {
        if (count($header) !== self::NUM_HEADER_COLUMNS) {
            return false;
        }

        list (
            $recordType,
            $fileType,
            $this->version,
            $this->sender,
            $this->onBehalfOf,
            $this->recipient,
            $this->reportDate,
            $this->identifier,
            $this->numRecords,
            $reportStartDate,
            $reportEndDate
            ) = $header;

        if ($recordType !== "HDR") {
            return false;
        }

        if ($fileType !== self::FILE_TYPE) {
            return false;
        }

        if (!in_array($this->version, self::SUPPORTED_VERSIONS)) {
            return false;
        }

        $this->header = $header;

        $this->reportDateTime = DateTime::createFromFormat("d/m/Y", $this->reportDate);
        $this->reportDateTime->setTime(0, 0, 0);

        $this->reportStartDate = DateTime::createFromFormat("d/m/Y", $reportStartDate);
        $this->reportStartDate->setTime(0, 0, 0);

        $this->reportEndDate = DateTime::createFromFormat("d/m/Y", $reportEndDate);
        $this->reportEndDate->setTime(0, 0, 0);

        return true;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return sprintf("%s_%s_%s_%s.txt",
            $this->sender,
            $this->recipient,
            self::FILE_TYPE,
            $this->identifier
        );
    }

    static function validateFilename(string $fileName): bool
    {
        // TODO: Implement validateFilename() method.
    }

    function writeRecords(string $fileName, array $records): void
    {
        // TODO: Implement writeRecords() method.
    }

    /**
     * @param string $fileName
     * @param callable $callback
     *
     * @throws \Exception
     */
    public function streamFromFile(string $fileName, callable $callback): void
    {
        parent::streamFromFile($fileName, function(array $row) use ($callback) {
            $record = DetailRecord::createFromRow($row);

            $callback($record);
        });
    }

    /**
     * @return DateTime
     */
    public function getReportStartDate(): DateTime
    {
        return $this->reportStartDate;
    }

    /**
     * @param DateTime $reportStartDate
     */
    public function setReportStartDate(DateTime $reportStartDate): void
    {
        $this->reportStartDate = $reportStartDate;
    }

    /**
     * @return DateTime
     */
    public function getReportEndDate(): DateTime
    {
        return $this->reportEndDate;
    }

    /**
     * @param DateTime $reportEndDate
     */
    public function setReportEndDate(DateTime $reportEndDate): void
    {
        $this->reportEndDate = $reportEndDate;
    }
}
