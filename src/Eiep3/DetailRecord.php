<?php

namespace Eiep\Eiep3;

use Eiep\DetailRecord as BaseDetailRecord;

use DateTime;

/**
 * Class DetailRecord
 *
 * @package Eiep\Eiep3
 */
class DetailRecord extends BaseDetailRecord
{
    const NUM_COLUMNS = 11;

    /**
     * Format the record for writing to the CSV file
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'DET',
            $this->icpIdentifier,
            $this->streamIdentifier,
            $this->readingType,
            $this->date->format('d/m/Y'),
            $this->tradingPeriod,
            $this->formatNumber($this->activeEnergy),
            $this->formatNumber($this->reactiveEnergy),
            $this->formatNumber($this->apparentEnergy),
            $this->flowDirection,
            $this->streamType ?: self::NULL_COLUMN
        ];
    }

    /**
     * @param array $data
     *
     * @return BaseDetailRecord|DetailRecord
     * @throws \Exception
     */
    public static function createFromRow(array $data)
    {
        $record = new DetailRecord();

        $numColumns = count($data);
        $expectedColumns = self::NUM_COLUMNS;

        if ($numColumns !== $expectedColumns) {
            throw new \Exception("Expected {$expectedColumns} columns but found {$numColumns}");
        }

        list (
            $recordType,
            $icpIdentifier,
            $streamIdentifier,
            $readingType,
            $date,
            $tradingPeriod,
            $activeEnergy,
            $reactiveEnergy,
            $apparentEnergy,
            $flowDirection,
            $streamType
            ) = $data;

        if ($recordType !== 'DET') {
            throw new \Exception("Record type {$recordType} is invalid (expecting HDR)");
        }

        $record->setIcpIdentifier($icpIdentifier);
        $record->setStreamIdentifier($streamIdentifier);
        $record->setReadingType($readingType);
        $record->setTradingPeriod($tradingPeriod);
        $record->setActiveEnergy($activeEnergy);
        $record->setReactiveEnergy($reactiveEnergy);
        $record->setApparentEnergy($apparentEnergy);
        $record->setFlowDirection($flowDirection);
        $record->setStreamType($streamType);

        $dateTime = DateTime::createFromFormat("d/m/Y", $date);
        $dateTime->setTime(0, 0, 0);

        $record->setDate($dateTime);

        return $record;
    }
}
