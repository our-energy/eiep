<?php

namespace Eiep\Eiep3;

use Eiep\Protocol;
use Eiep\EiepInterface;
use League\Csv\Writer;
use DateTime;

/**
 * Class Report
 *
 * @package Eiep\Eiep3
 */
class Report implements EiepInterface
{
    use Protocol;

    const FILE_TYPE = 'ICPHH';

    const VERSION_10 = '10.0';
    const VERSION_11 = '11.0';

    const SUPPORTED_VERSIONS = [
        self::VERSION_10,
        self::VERSION_11
    ];

    const DEFAULT_VERSION = self::VERSION_11;
    const NUM_HEADER_COLUMNS = 13;

    const DATE_FORMAT = 'd/m/Y';

    const FILE_STATUS_INITIAL = 'I';
    const FILE_STATUS_REPLACEMENT = 'R';
    const FILE_STATUS_PARTIAL_UPDATE = 'X';

    const UTILITY_TYPE_GAS = 'G';
    const UTILITY_TYPE_ELECTRICITY = 'E';

    const FILENAME_REGEX = "/^([A-Z]{4})_([A-Z])_([A-Z]{4})_([A-Z]{1,7})_([0-9]{6})_([0-9]{8})_(.*?).(csv|txt)$/";

    /**
     * @var string
     */
    private $utilityType;

    /**
     * @var string
     */
    private $fileStatus;

    /**
     * Report constructor.
     *
     * @param null $resource
     *
     * @throws \Exception
     */
    public function __construct($resource = null)
    {
        $this->version = self::DEFAULT_VERSION;
        $this->setReportDate(new DateTime());

        if (is_resource($resource)) {
            $this->readHeaderFromStream($resource);
            $this->stream = $resource;
        } else if (is_string($resource)) {
            $this->readHeaderFromFile($resource);
        }
    }

    /**
     * @return array
     */
    public function getHeader(): array
    {
        return [
            'HDR',
            self::FILE_TYPE,
            $this->version,
            substr($this->sender, 0, 4),
            substr($this->onBehalfOf, 0, 4),
            substr($this->recipient, 0, 4),
            $this->reportDate,
            $this->reportTime,
            substr($this->identifier, 0, 15),
            str_pad($this->numRecords, 8, "0", STR_PAD_LEFT),
            $this->reportMonth,
            $this->utilityType,
            $this->fileStatus
        ];
    }

    /**
     * @param array $header
     *
     * @return bool
     */
    public function validateHeader(array $header): bool
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
            $this->reportTime,
            $this->identifier,
            $this->numRecords,
            $this->reportMonth,
            $this->utilityType,
            $this->fileStatus
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

        if (!in_array($this->utilityType, [self::UTILITY_TYPE_ELECTRICITY, self::UTILITY_TYPE_GAS])) {
            return false;
        }

        if (!in_array($this->fileStatus, [self::FILE_STATUS_INITIAL, self::FILE_STATUS_REPLACEMENT, self::FILE_STATUS_PARTIAL_UPDATE])) {
            return false;
        }

        $this->header = $header;

        $this->reportDateTime = DateTime::createFromFormat(self::DATE_FORMAT, $this->reportDate);
        $this->reportDateTime->setTime(0, 0, 0);

        return true;
    }

    /**
     * @param string $fileName
     *
     * @return bool
     */
    public static function validateFilename(string $fileName): bool
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
     * @param callable $callback
     *
     * @throws \Exception
     */
    public function streamFromFile(string $fileName, callable $callback): void
    {
        $this->createReadStream($fileName, function (array $row) use ($callback) {
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
        $this->readStream($stream, function (array $row) use ($callback) {
            $record = DetailRecord::createFromRow($row);

            $callback($record);
        });
    }

    /**
     * @param callable $callback
     *
     * @throws \Exception
     */
    function read(callable $callback): void
    {
        if (!is_resource($this->stream)) {
            throw new \Exception("No valid stream supplied");
        }

        $this->readStream($this->stream, function (array $row) use ($callback) {
            $record = DetailRecord::createFromRow($row);

            $callback($record);
        });
    }

    /**
     * @param string $fileName
     *
     * @return Writer
     * @throws \League\Csv\CannotInsertRecord
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
     * @param string $fileName
     * @param array $records
     *
     * @throws \League\Csv\CannotInsertRecord
     */
    public function writeRecords(string $fileName, array $records): void
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
     * @return string
     */
    public function getUtilityType(): string
    {
        return $this->utilityType;
    }

    /**
     * @param string $utilityType
     *
     * @return Report
     * @throws \Exception
     */
    public function setUtilityType(string $utilityType): Report
    {
        if (!in_array($utilityType, [self::UTILITY_TYPE_ELECTRICITY, self::UTILITY_TYPE_GAS])) {
            throw new \Exception("Utility type {$utilityType} is invalid");
        }

        $this->utilityType = $utilityType;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileStatus(): string
    {
        return $this->fileStatus;
    }

    /**
     * @param string $fileStatus
     *
     * @return Report
     * @throws \Exception
     */
    public function setFileStatus(string $fileStatus): Report
    {
        if (!in_array($fileStatus, [self::FILE_STATUS_INITIAL, self::FILE_STATUS_REPLACEMENT, self::FILE_STATUS_PARTIAL_UPDATE])) {
            throw new \Exception("File status {$fileStatus} is invalid");
        }

        $this->fileStatus = $fileStatus;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return sprintf("%s_%s_%s_%s_%s_%s_%s.txt",
            $this->sender,
            $this->utilityType,
            $this->recipient,
            self::FILE_TYPE,
            $this->reportMonth,
            $this->reportDateTime->format("Ymd"),
            $this->identifier
        );
    }
}
