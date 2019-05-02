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
    const DATE_FORMAT = 'd/m/Y';

    const RESPONSE_ACCEPTED = '000';
    const RESPONSE_REJECTED_NO_ADDRESS = '001';
    const RESPONSE_REJECTED_NO_ICP = '002';
    const RESPONSE_REJECTED_NO_CUSTOMER = '003';
    const RESPONSE_REJECTED_NO_AUTHORITY = '004';

    const STATUS_ACTUAL = 'RD';
    const STATUS_ESTIMATED = 'ES';

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
     * DetailRecord constructor.
     */
    public function __construct()
    {
        $this->setReadPeriodStart(new DateTime());
        $this->setReadPeriodEnd(new DateTime());
    }

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
        $record->setActiveEnergy((float)$activeUnits);
        $record->setReactiveEnergy((float)$reactiveUnits);

        $record->setReadPeriodStart(DateTime::createFromFormat(self::TIME_FORMAT, $readPeriodStart));
        $record->setReadPeriodEnd(DateTime::createFromFormat(self::TIME_FORMAT, $readPeriodEnd));

        return $record;
    }

    function toArray(): array
    {
        return [
            'DET',
            substr($this->authorisationCode, 0, 20),
            substr($this->icpIdentifier, 0, 15),
            substr($this->responseCode, 0, 3),
            substr($this->nzDstAdjustment, 0, 4),
            substr($this->meteringComponent, 0, 30),
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
     *
     * @return DetailRecord
     */
    public function setAuthorisationCode(string $authorisationCode): DetailRecord
    {
        $this->authorisationCode = $authorisationCode;

        return $this;
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
     *
     * @return DetailRecord
     * @throws \Exception
     */
    public function setResponseCode(string $responseCode): DetailRecord
    {
        if (!in_array($responseCode, [
            self::RESPONSE_ACCEPTED,
            self::RESPONSE_REJECTED_NO_ADDRESS,
            self::RESPONSE_REJECTED_NO_ICP,
            self::RESPONSE_REJECTED_NO_CUSTOMER,
            self::RESPONSE_REJECTED_NO_AUTHORITY
        ])) {
            throw new \Exception("Invalid response code");
        }

        $this->responseCode = $responseCode;

        return $this;
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
     *
     * @return DetailRecord
     */
    public function setNzDstAdjustment(string $nzDstAdjustment): DetailRecord
    {
        $this->nzDstAdjustment = $nzDstAdjustment;

        return $this;
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
     *
     * @return DetailRecord
     */
    public function setMeteringComponent(string $meteringComponent): DetailRecord
    {
        $this->meteringComponent = $meteringComponent;

        return $this;
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
     *
     * @return DetailRecord
     */
    public function setRegisterCode(string $registerCode): DetailRecord
    {
        $this->registerCode = $registerCode;

        return $this;
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
     *
     * @return DetailRecord
     */
    public function setAvailabilityPeriod(string $availabilityPeriod): DetailRecord
    {
        $this->availabilityPeriod = $availabilityPeriod;

        return $this;
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
     *
     * @return DetailRecord
     */
    public function setReadPeriodStart(DateTime $readPeriodStart): DetailRecord
    {
        $this->readPeriodStart = $readPeriodStart;

        return $this;
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
     *
     * @return DetailRecord
     */
    public function setReadPeriodEnd(DateTime $readPeriodEnd): DetailRecord
    {
        $this->readPeriodEnd = $readPeriodEnd;

        return $this;
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
     *
     * @return DetailRecord
     * @throws \Exception
     */
    public function setReadStatus(string $readStatus): DetailRecord
    {
        if (!in_array($readStatus, [self::STATUS_ACTUAL, self::STATUS_ESTIMATED])) {
            throw new \Exception("Invalid read status");
        }

        $this->readStatus = $readStatus;

        return $this;
    }


}
