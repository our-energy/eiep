<?php

namespace Eiep\Tests\Eiep3;

use Eiep\Eiep3\DetailRecord;
use PHPUnit\Framework\TestCase;

class DetailRecordTest extends TestCase
{
    public function testCSVFormat()
    {
        $record = new DetailRecord();
        $recordDate = new \DateTime("2019-04-27 00:00:00");

        $record
            ->setIcpIdentifier("1234567890")
            ->setStreamIdentifier("ABCDEFG")
            ->setReadingType(DetailRecord::READING_TYPE_FINAL)
            ->setDate($recordDate)
            ->setTradingPeriod(48)
            ->setActiveEnergy(1)
            ->setReactiveEnergy(2)
            ->setApparentEnergy(3)
            ->setFlowDirection(DetailRecord::FLOW_DIRECTION_EXTRACT);

        $this->assertEquals($record->toArray(), [
            'DET',
            '1234567890',
            'ABCDEFG',
            'F',
            '27/04/2019',
            48,
            '1.00',
            '2.00',
            '3.00',
            'X',
            'null'
        ]);
    }

    public function testCreateFromRow()
    {
        $row = [
            'DET',
            '1234567890',
            'ABCDEFG',
            'F',
            '27/04/2019',
            48,
            '1.00',
            '2.00',
            '3.00',
            'X',
            null
        ];

        $record = DetailRecord::createFromRow($row);

        $this->assertEquals($record->getIcpIdentifier(), '1234567890');
        $this->assertEquals($record->getStreamIdentifier(), 'ABCDEFG');
        $this->assertEquals($record->getReadingType(), 'F');
        $this->assertEquals($record->getDate()->format("d/m/Y"), '27/04/2019');
        $this->assertEquals($record->getTradingPeriod(), 48);
        $this->assertEquals($record->getActiveEnergy(), 1);
        $this->assertEquals($record->getReactiveEnergy(), 2);
        $this->assertEquals($record->getApparentEnergy(), 3);
        $this->assertEquals($record->getFlowDirection(), DetailRecord::FLOW_DIRECTION_EXTRACT);
    }

    public function testCreateFromInvalidRow()
    {
        $row = [1,2,3,4,5,6,7,8,9,10,11];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Record type 1 is invalid (expecting HDR)");

        DetailRecord::createFromRow($row);
    }

    public function testCreateFromEmptyRow()
    {
        $row = [];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Expected 11 columns but found 0");

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
}
