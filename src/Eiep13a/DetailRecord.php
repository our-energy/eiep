<?php

namespace Eiep\Eiep13a;

use Eiep\DetailRecord as BaseDetailRecord;

use DateTime;

/**
 * Class DetailRecord
 *
 * @package Eiep\Eiep13a
 */
class DetailRecord extends BaseDetailRecord
{
    const NUM_COLUMNS = 14;
    const TIME_FORMAT = 'd/m/Y H:i:s';

    /**
     * @var string
     */
    private $authorisationCode;

    /**
     * @var string
     */
    private $responseCode;

    /**
     * @var string
     */
    private $nzDstAdjustment;

    /**
     * @var string
     */
    private $meteringComponent;

    /**
     * @var string
     */
    private $registerCode;

    /**
     * @var string
     */
    private $availabilityPeriod;

    /**
     * @var DateTime
     */
    private $readPeriodStart;

    /**
     * @var DateTime
     */
    private $readPeriodEnd;

    /**
     * @var string
     */
    private $readStatus;

    /**
     * @param array $data
     *
     * @return BaseDetailRecord|DetailRecord
     * @throws \Exception
     */
    static function createFromRow(array $data)
    {
        $record = new DetailRecord();

        $numColumns = count($data);
        $expectedColumns = self::NUM_COLUMNS;

        if ($numColumns !== $expectedColumns) {
            throw new \Exception("Expected {$expectedColumns} columns but found {$numColumns}");
        }

        list (
            $recordType,
            $authorisationCode,
            $icpIdentifier,
            $responseCode,
            $nzDstAdjustment,
            $meteringComponent,
            $flowDirection,
            $registerCode,
            $availabilityPeriod,
            $readPeriodStart,
            $readPeriodEnd,
            $readStatus,
            $activeUnits,
            $reactiveUnits
            ) = $data;

        if ($recordType !== 'DET') {
            throw new \Exception("Record type {$recordType} is invalid (expecting HDR)");
        }

        $record->setAuthorisationCode($authorisationCode);
        $record->setIcpIdentifier($icpIdentifier);
        $record->setResponseCode($responseCode);
        $record->setNzDstAdjustment($nzDstAdjustment);
        $record->setMeteringComponent($meteringComponent);
        $record->setFlowDirection($flowDirection);
        $record->setRegisterCode($registerCode);
        $record->setAvailabilityPeriod($availabilityPeriod);
        $record->setReadStatus($readStatus);
        $record->setActiveEnergy($activeUnits);
        $record->setReactiveEnergy($reactiveUnits);

        $record->setReadPeriodStart(DateTime::createFromFormat(self::TIME_FORMAT, $readPeriodStart));
        $record->setReadPeriodEnd(DateTime::createFromFormat(self::TIME_FORMAT, $readPeriodEnd));

        return $record;
    }

    function toArray(): array
    {
        return [
            'DET',
            $this->authorisationCode,
            $this->icpIdentifier,
            $this->responseCode,
            $this->nzDstAdjustment,
            $this->meteringComponent,
            $this->flowDirection,
            $this->registerCode,
            $this->availabilityPeriod,
            $this->readPeriodStart->format(self::TIME_FORMAT),
            $this->readPeriodEnd->format(self::TIME_FORMAT),
            $this->readStatus,
            $this->activeEnergy,
            $this->reactiveEnergy
        ];
    }

    /**
     * @param string $authorisationCode
     */
    public function setAuthorisationCode(string $authorisationCode): void
    {
        $this->authorisationCode = $authorisationCode;
    }

    /**
     * @return string
     */
    public function getAuthorisationCode(): string
    {
        return $this->authorisationCode;
    }

    /**
     * @param string $responseCode
     */
    public function setResponseCode(string $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    /**
     * @return string
     */
    public function getResponseCode(): string
    {
        return $this->responseCode;
    }

    /**
     * @return string
     */
    public function getNzDstAdjustment(): string
    {
        return $this->nzDstAdjustment;
    }

    /**
     * @param string $nzDstAdjustment
     */
    public function setNzDstAdjustment(string $nzDstAdjustment): void
    {
        $this->nzDstAdjustment = $nzDstAdjustment;
    }

    /**
     * @return string
     */
    public function getMeteringComponent(): string
    {
        return $this->meteringComponent;
    }

    /**
     * @param string $meteringComponent
     */
    public function setMeteringComponent(string $meteringComponent): void
    {
        $this->meteringComponent = $meteringComponent;
    }

    /**
     * @return string
     */
    public function getRegisterCode(): string
    {
        return $this->registerCode;
    }

    /**
     * @param string $registerCode
     */
    public function setRegisterCode(string $registerCode): void
    {
        $this->registerCode = $registerCode;
    }

    /**
     * @return string
     */
    public function getAvailabilityPeriod(): string
    {
        return $this->availabilityPeriod;
    }

    /**
     * @param string $availabilityPeriod
     */
    public function setAvailabilityPeriod(string $availabilityPeriod): void
    {
        $this->availabilityPeriod = $availabilityPeriod;
    }

    /**
     * @return DateTime
     */
    public function getReadPeriodStart(): DateTime
    {
        return $this->readPeriodStart;
    }

    /**
     * @param DateTime $readPeriodStart
     */
    public function setReadPeriodStart(DateTime $readPeriodStart): void
    {
        $this->readPeriodStart = $readPeriodStart;
    }

    /**
     * @return DateTime
     */
    public function getReadPeriodEnd(): DateTime
    {
        return $this->readPeriodEnd;
    }

    /**
     * @param DateTime $readPeriodEnd
     */
    public function setReadPeriodEnd(DateTime $readPeriodEnd): void
    {
        $this->readPeriodEnd = $readPeriodEnd;
    }

    /**
     * @return string
     */
    public function getReadStatus(): string
    {
        return $this->readStatus;
    }

    /**
     * @param string $readStatus
     */
    public function setReadStatus(string $readStatus): void
    {
        $this->readStatus = $readStatus;
    }
}
