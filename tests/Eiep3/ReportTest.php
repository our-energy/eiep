<?php

namespace Eiep\Tests\Eiep3;

use Eiep\Eiep3\DetailRecord;
use Eiep\Eiep3\Report;
use PHPUnit\Framework\TestCase;

class ReportTest extends TestCase
{
    public function testValidateFileName()
    {
        $this->assertTrue(Report::validateFilename("SNDR_E_RCPT_ICPHH_201904_20190421_000.csv"));
        $this->assertFalse(Report::validateFilename("SNDR_E_RCPT_ICPCONS_201904_20190421_000.csv"));
        $this->assertFalse(Report::validateFilename("test file.csv"));
    }

    public function testReadFile()
    {
        $report = new Report();

        $fileName = "tests/Data/eiep3-valid.txt";

        $records = [];
        $icps = [];

        $report->streamFromFile($fileName, function (DetailRecord $record) use (&$records, &$icps) {
            $records[] = $record;
            $icps[] = $record->getIcpIdentifier();
        });

        $this->assertEquals($report->getNumRecords(), count($records));
        $this->assertEquals($report->getNumRecords(),96);
        $this->assertEquals($report->getUtilityType(), 'E');
        $this->assertEquals($report->getFileStatus(), 'I');
        $this->assertEquals($report->getVersion(), '10.0');
        $this->assertEquals($report->getSender(), 'SNDR');
        $this->assertEquals($report->getOnBehalfOf(), 'BHLF');
        $this->assertEquals($report->getRecipient(), 'RCPT');
        $this->assertEquals($report->getIdentifier(), '000');
        $this->assertEquals($report->getReportDate(), '21/04/2019');
        $this->assertEquals($report->getReportMonth(), '201904');
        $this->assertEquals($report->getReportTime(), '21:11:04');
        $this->assertEquals($report->getHeader(), [
            'HDR', 'ICPHH', '10.0', 'SNDR', 'BHLF', 'RCPT', '21/04/2019', '21:11:04', '000', '00000096', '201904', 'E', 'I'
        ]);

        $icps = array_values(array_unique($icps));
        $this->assertEquals(count($icps), 2);
        $this->assertEquals($icps, [
            'XXXXXXXXXXXXXXX',
            'AAAAAAAAAAAAAAA'
        ]);

        $this->assertEquals($report->getFileName(), "SNDR_E_RCPT_ICPHH_201904_20190421_000.txt");
    }

    public function testReadEmptyFile()
    {
        $report = new Report();

        $fileName = "tests/Data/eiep3-empty.txt";

        $records = [];

        $report->streamFromFile($fileName, function (DetailRecord $record) use (&$records) {
            $records[] = $record;
        });

        $this->assertEquals($report->getNumRecords(), count($records));
        $this->assertEquals($report->getNumRecords(),0);
    }

    public function testSetFileStatus()
    {
        $report = new Report();

        $report->setFileStatus(Report::FILE_STATUS_REPLACEMENT);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("File status something is invalid");

        $report->setFileStatus("something");
    }

    public function testSetUtilityType()
    {
        $report = new Report();

        $report->setUtilityType(Report::UTILITY_TYPE_ELECTRICITY);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Utility type Nuclear is invalid");

        $report->setUtilityType("Nuclear");
    }

    public function testInvalidHeader()
    {
        $report = new Report();

        $fileName = "tests/Data/eiep3-invalid.txt";

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("HDR record is in an invalid format");

        $report->streamFromFile($fileName, function (DetailRecord $record) {

        });
    }

    public function testWriteNoRecords()
    {
        $report = new Report();
        $report->setReportDate(new \DateTime("2019-04-27 00:00:00"));

        $report->writeRecords("/tmp/output.txt", []);

        $output = file_get_contents("/tmp/output.txt");

        $this->assertEquals($output, "HDR,ICPHH,10.0,,,,27/04/2019,00:00:00,,00000000,201904,," . PHP_EOL);
    }

    public function testWriteEmptyRecords()
    {
        $report = new Report();
        $report->setReportDate(new \DateTime("2019-04-27 00:00:00"));

        $records = [
            new DetailRecord(),
            new DetailRecord()
        ];

        $report->writeRecords("/tmp/output.txt", $records);
        $output = file_get_contents("/tmp/output.txt");

        $expected = <<<CSV
HDR,ICPHH,10.0,,,,27/04/2019,00:00:00,,00000002,201904,,
DET,,,,27/04/2019,,null,null,null,,null
DET,,,,27/04/2019,,null,null,null,,null

CSV;

        $this->assertEquals($output, $expected);
        $this->assertEquals($report->getNumRecords(), 2);
    }

    public function testWriteCompleteRecords()
    {
        $report = new Report();
        $report->setReportDate(new \DateTime("2019-04-27 00:00:00"));
        $report->setUtilityType(Report::UTILITY_TYPE_ELECTRICITY);
        $report->setFileStatus(Report::FILE_STATUS_REPLACEMENT);
        $report->setNumRecords(1);
        $report->setSender("Company");
        $report->setRecipient("Recipient");
        $report->setOnBehalfOf("Behalf");
        $report->setIdentifier("1234567890");

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

        $records = [
            $record,
            $record,
            $record
        ];

        $report->writeRecords("/tmp/output.txt", $records);
        $output = file_get_contents("/tmp/output.txt");

        $expected = <<<CSV
HDR,ICPHH,10.0,Comp,Beha,Reci,27/04/2019,00:00:00,1234567890,00000003,201904,E,R
DET,1234567890,ABCDEFG,F,27/04/2019,48,1.00,2.00,3.00,X,null
DET,1234567890,ABCDEFG,F,27/04/2019,48,1.00,2.00,3.00,X,null
DET,1234567890,ABCDEFG,F,27/04/2019,48,1.00,2.00,3.00,X,null

CSV;

        $this->assertEquals($output, $expected);
    }
}
