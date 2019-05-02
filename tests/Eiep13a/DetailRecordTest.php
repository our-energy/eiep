<?php

namespace Eiep\Tests\Eiep13a;

use Eiep\Eiep13a\DetailRecord;
use PHPUnit\Framework\TestCase;

class DetailRecordTest extends TestCase
{
    public function testCSVFormat()
    {
        $record = new DetailRecord();
        $recordDate = new \DateTime("2019-04-27 00:00:00");

        $record
            ->setAuthorisationCode("AAAAAAAAAAAAAAAAAAAA")
            ->setIcpIdentifier("1234567890")
            ->setResponseCode(DetailRecord::RESPONSE_ACCEPTED)
            ->setNzDstAdjustment("NZST")
            ->setMeteringComponent("CCCCCCCCCCCCCCCCCCCCCCCCCCCCCC")
            ->setFlowDirection(DetailRecord::FLOW_DIRECTION_EXTRACT)
            ->setRegisterCode("000000")
            ->setAvailabilityPeriod("000000")
            ->setReadPeriodStart($recordDate)
            ->setReadPeriodEnd($recordDate)
            ->setReadStatus(DetailRecord::STATUS_ACTUAL)
            ->setActiveEnergy(1)
            ->setReactiveEnergy(2);

        $this->assertEquals($record->toArray(), [
            'DET',
            'AAAAAAAAAAAAAAAAAAAA',
            '1234567890',
            '000',
            'NZST',
            'CCCCCCCCCCCCCCCCCCCCCCCCCCCCCC',
            'X',
            '000000',
            '000000',
            '27/04/2019 00:00:00',
            '27/04/2019 00:00:00',
            'RD',
            '1',
            '2'
        ]);
    }

    public function testCreateFromRow()
    {
        $row = [
            'DET',
            'AAAAAAAAAAAAAAAAAAAA',
            '1234567890',
            '000',
            'NZST',
            'CCCCCCCCCCCCCCCCCCCCCCCCCCCCCC',
            'X',
            '000000',
            '000000',
            '27/04/2019 00:00:00',
            '27/04/2019 00:00:00',
            'RD',
            '1',
            '2'
        ];

        $record = DetailRecord::createFromRow($row);

        $this->assertEquals($record->getAuthorisationCode(), 'AAAAAAAAAAAAAAAAAAAA');
        $this->assertEquals($record->getIcpIdentifier(), '1234567890');
        $this->assertEquals($record->getResponseCode(), '000');
        $this->assertEquals($record->getNzDstAdjustment(), 'NZST');
        $this->assertEquals($record->getMeteringComponent(), 'CCCCCCCCCCCCCCCCCCCCCCCCCCCCCC');
        $this->assertEquals($record->getFlowDirection(), 'X');
        $this->assertEquals($record->getRegisterCode(), '000000');
        $this->assertEquals($record->getAvailabilityPeriod(), '000000');
        $this->assertEquals($record->getReadPeriodStart()->format("d/m/Y"), "27/04/2019");
        $this->assertEquals($record->getReadPeriodEnd()->format("d/m/Y"), "27/04/2019");
        $this->assertEquals($record->getReadStatus(), "RD");
        $this->assertEquals($record->getActiveEnergy(), 1);
        $this->assertEquals($record->getReactiveEnergy(), 2);
    }

    public function testCreateFromInvalidRow()
    {
        $row = [1,2,3,4,5,6,7,8,9,10,11,12,13,14];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Record type 1 is invalid (expecting HDR)");

        DetailRecord::createFromRow($row);
    }

    public function testCreateFromEmptyRow()
    {
        $row = [];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Expected 14 columns but found 0");

        DetailRecord::createFromRow($row);
    }

    public function testSetFlowDirection()
    {
        $record = new DetailRecord();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid energy flow direction");

        $record->setFlowDirection('Z');
    }

    public function testSetReadingType()
    {
        $record = new DetailRecord();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid reading type");

        $record->setReadingType('Z');
    }

    public function testSetTradingPeriod()
    {
        $record = new DetailRecord();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid trading period 51, expected 1 - 50");

        $record->setTradingPeriod(51);
    }

    public function testSetResponseCode()
    {
        $record = new DetailRecord();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid response code");

        $record->setResponseCode("111");
    }

    public function testSetReadStatus()
    {
        $record = new DetailRecord();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid read status");

        $record->setReadStatus("AA");
    }
}
