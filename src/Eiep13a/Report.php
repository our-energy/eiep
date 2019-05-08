<?php

namespace Eiep\Eiep13a;

use Eiep\Protocol;
use Eiep\EiepInterface;
use DateTime;
use League\Csv\Writer;

/**
 * Class Report
 *
 * @package Eiep\Eiep13a
 */
class Report implements EiepInterface
{
    use Protocol;

    const FILE_TYPE = 'ICPCONS';
    const SUPPORTED_VERSIONS = ['1.1'];
    const DEFAULT_VERSION = '1.1';
    const NUM_HEADER_COLUMNS = 11;
    const DATE_FORMAT = 'd/m/Y';

    const FILENAME_REGEX = "/^([A-Z]{4})_([A-Z])_([A-Z]{4})_([A-Z]{1,7})_([0-9]{6})_([0-9]{8})_(.*?).(csv|txt)$/";

    /**
     * @var DateTime
     */
    private $reportStartDate;

    /**
     * @var DateTime
     */
    private $reportEndDate;

    /**
     * Report constructor.
     */
    public function __construct()
    {
        $this->version = self::DEFAULT_VERSION;
        $this->setReportDate(new DateTime());
        $this->setReportStartDate(new DateTime());
        $this->setReportEndDate(new DateTime());
    }

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
            $this->reportStartDate->format(self::DATE_FORMAT),
            $this->reportEndDate->format(self::DATE_FORMAT)
        ];
    }

    /**
     * @param array $header
     *
     * @return bool
     */
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

        $this->reportDateTime = DateTime::createFromFormat(self::DATE_FORMAT, $this->reportDate);
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

    /**
     * @param string $fileName
     *
     * @return bool
     */
    static function validateFilename(string $fileName): bool
    {
        $matches = [];

        if (preg_match(self::FILENAME_REGEX, $fileName, $matches)) {
            list (,
                $sender,
                $utilityType,
                $recipient,
                $fileType,
                $reportMonth,
                $reportDate,
                $identifier
                ) = $matches;

            if ($fileType !== self::FILE_TYPE) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $fileName
     * @param array $records
     *
     * @throws \League\Csv\CannotInsertRecord
     */
    function writeRecords(string $fileName, array $records): void
    {
        $stream = fopen($fileName, 'w');

        $writer = Writer::createFromStream($stream);

        $this->setNumRecords(count($records));

        // Write the HDR
        $writer->insertOne($this->getHeader());

        // Write the DET items
        $writer->insertAll(array_map(function (DetailRecord $record) {
            return $record->toArray();
        }, $records));
    }

    /**
     * @param string $fileName
     * @param callable $callback
     *
     * @throws \Exception
     */
    public function streamFromFile(string $fileName, callable $callback): void
    {
        $this->createReadStream($fileName, function(array $row) use ($callback) {
            $record = DetailRecord::createFromRow($row);

            $callback($record);
        });
    }

    /**
     * @param $stream
     * @param callable $callback
     *
     * @throws \Exception
     */
    public function readFromStream($stream, callable $callback): void
    {
        $this->readStream($stream, function(array $row) use ($callback) {
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
     *
     * @return Report
     */
    public function setReportStartDate(DateTime $reportStartDate): Report
    {
        $this->reportStartDate = $reportStartDate;

        return $this;
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
     *
     * @return Report
     */
    public function setReportEndDate(DateTime $reportEndDate): Report
    {
        $this->reportEndDate = $reportEndDate;

        return $this;
    }
}
