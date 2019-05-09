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

    public function testReadStream()
    {
        $fileName = "tests/Data/eiep3-valid.txt";

        $stream = fopen($fileName, "r");
        $records = [];

        $report = new Report();

        $report->readFromStream($stream, function (DetailRecord $record) use (&$records) {
            $records[] = $record;
        });

        $this->assertEquals($report->getNumRecords(), count($records));
        $this->assertEquals($report->getNumRecords(),96);
    }

    public function testReadInvalidStream()
    {
        $stream = false;

        $report = new Report();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Stream is not a valid resource");

        $report->readFromStream($stream, function (DetailRecord $record) {

        });
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

        $report->writeRecords("/tmp/output-eiep3.txt", []);

        $output = file_get_contents("/tmp/output-eiep3.txt");

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

        $report->writeRecords("/tmp/output-eiep3.txt", $records);
        $output = file_get_contents("/tmp/output-eiep3.txt");

        $currentDate = date(DetailRecord::DATE_FORMAT);

        $expected = <<<CSV
HDR,ICPHH,10.0,,,,27/04/2019,00:00:00,,00000002,201904,,
DET,,,,$currentDate,,null,null,null,,null
DET,,,,$currentDate,,null,null,null,,null

CSV;

        $this->assertEquals($output, $expected);
        $this->assertEquals($report->getNumRecords(), 2);
    }

    public function testWriteCompleteRecords()
    {
        $report = new Report();
        $report
            ->setReportDate(new \DateTime("2019-04-27 00:00:00"))
            ->setUtilityType(Report::UTILITY_TYPE_ELECTRICITY)
            ->setFileStatus(Report::FILE_STATUS_REPLACEMENT)
            ->setNumRecords(1)
            ->setSender("Company")
            ->setRecipient("Recipient")
            ->setOnBehalfOf("Behalf")
            ->setIdentifier("1234567890");

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

        $report->writeRecords("/tmp/output-eiep3.txt", $records);
        $output = file_get_contents("/tmp/output-eiep3.txt");

        $expected = <<<CSV
HDR,ICPHH,10.0,Comp,Beha,Reci,27/04/2019,00:00:00,1234567890,00000003,201904,E,R
DET,1234567890,ABCDEFG,F,27/04/2019,48,1.00,2.00,3.00,X,null
DET,1234567890,ABCDEFG,F,27/04/2019,48,1.00,2.00,3.00,X,null
DET,1234567890,ABCDEFG,F,27/04/2019,48,1.00,2.00,3.00,X,null

CSV;

        $this->assertEquals($output, $expected);
    }

    public function testWriteStream()
    {
        $report = new Report();
        $report
            ->setReportDate(new \DateTime("2019-04-27 00:00:00"))
            ->setUtilityType(Report::UTILITY_TYPE_ELECTRICITY)
            ->setFileStatus(Report::FILE_STATUS_REPLACEMENT)
            ->setNumRecords(3)
            ->setSender("Company")
            ->setRecipient("Recipient")
            ->setOnBehalfOf("Behalf")
            ->setIdentifier("1234567890");

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

        $writer = $report->createWriter("/tmp/output-eiep3.txt");

        $writer->insertAll(array_map(function (DetailRecord $record) {
            return $record->toArray();
        }, $records));

        $output = file_get_contents("/tmp/output-eiep3.txt");

        $expected = <<<CSV
HDR,ICPHH,10.0,Comp,Beha,Reci,27/04/2019,00:00:00,1234567890,00000003,201904,E,R
DET,1234567890,ABCDEFG,F,27/04/2019,48,1.00,2.00,3.00,X,null
DET,1234567890,ABCDEFG,F,27/04/2019,48,1.00,2.00,3.00,X,null
DET,1234567890,ABCDEFG,F,27/04/2019,48,1.00,2.00,3.00,X,null

CSV;

        $this->assertEquals($output, $expected);
    }

    public function testReadHeader()
    {
        $fileName = "tests/Data/eiep3-valid.txt";

        $report = new Report($fileName);

        $this->assertEquals($report->getHeader(), [
            'HDR', 'ICPHH', '10.0', 'SNDR', 'BHLF', 'RCPT', '21/04/2019', '21:11:04', '000', '00000096', '201904', 'E', 'I'
        ]);
    }

    public function testReadHeaderNotFound()
    {
        $fileName = "something.txt";

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("File not found: something.txt");

        $report = new Report($fileName);
    }

    public function testReadHeaderFromStream()
    {
        $fileName = "tests/Data/eiep3-valid.txt";

        $stream = fopen($fileName, "r");

        $report = new Report($stream);

        $this->assertEquals($report->getHeader(), [
            'HDR', 'ICPHH', '10.0', 'SNDR', 'BHLF', 'RCPT', '21/04/2019', '21:11:04', '000', '00000096', '201904', 'E', 'I'
        ]);
    }

    public function testRead()
    {
        $fileName = "tests/Data/eiep3-valid.txt";

        $report = new Report($fileName);

        $count = 0;

        $report->read(function (DetailRecord $record)  use (&$count) {
            $count++;
        });

        $this->assertEquals(96, $count);
        $this->assertEquals(96, $report->getNumRecords());
    }

    public function testReadWithoutStream()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("No valid stream supplie");

        $report = new Report();

        $report->read(function (DetailRecord $record) {

        });
    }
}
