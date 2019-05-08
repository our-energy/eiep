<?php

namespace Eiep\Tests\Eiep13a;

use Eiep\Eiep13a\DetailRecord;
use Eiep\Eiep13a\Report;
use PHPUnit\Framework\TestCase;

class ReportTest extends TestCase
{
    public function testValidateFileName()
    {
        $this->assertTrue(Report::validateFilename("SNDR_E_RCPT_ICPCONS_201904_20190421_000.csv"));
        $this->assertFalse(Report::validateFilename("SNDR_E_RCPT_ICPHH_201904_20190421_000.csv"));
        $this->assertFalse(Report::validateFilename("test file.csv"));
    }

    public function testReadFile()
    {
        $report = new Report();

        $fileName = "tests/Data/eiep13a-valid.txt";

        $records = [];
        $icps = [];

        $report->streamFromFile($fileName, function (DetailRecord $record) use (&$records, &$icps) {
            $records[] = $record;
            $icps[] = $record->getIcpIdentifier();
        });

        $this->assertEquals($report->getNumRecords(), count($records));
        $this->assertEquals($report->getNumRecords(),48);
        $this->assertEquals($report->getVersion(), '1.1');
        $this->assertEquals($report->getSender(), 'SNDR');
        $this->assertEquals($report->getOnBehalfOf(), 'BHLF');
        $this->assertEquals($report->getRecipient(), 'RCPT');
        $this->assertEquals($report->getIdentifier(), '000');
        $this->assertEquals($report->getReportDate(), '21/04/2019');
        $this->assertEquals($report->getReportStartDate()->format("d/m/Y"), '01/10/2014');
        $this->assertEquals($report->getReportEndDate()->format("d/m/Y"), '22/03/2016');

        $this->assertEquals($report->getHeader(), [
            'HDR', 'ICPCONS', '1.1', 'SNDR', 'BHLF', 'RCPT', '21/04/2019', '000', '00000048', '01/10/2014', '22/03/2016'
        ]);

        $icps = array_values(array_unique($icps));
        $this->assertEquals(count($icps), 1);
        $this->assertEquals($icps, [
            'XXXXXXXXXXXXXXX'
        ]);

        $this->assertEquals($report->getFileName(), "SNDR_RCPT_ICPCONS_000.txt");
    }

    public function testReadStream()
    {
        $fileName = "tests/Data/eiep13a-valid.txt";

        $stream = fopen($fileName, "r");
        $records = [];

        $report = new Report();

        $report->readFromStream($stream, function (DetailRecord $record) use (&$records) {
            $records[] = $record;
        });

        $this->assertEquals($report->getNumRecords(), count($records));
        $this->assertEquals($report->getNumRecords(),48);
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

        $fileName = "tests/Data/eiep13a-empty.txt";

        $records = [];

        $report->streamFromFile($fileName, function (DetailRecord $record) use (&$records) {
            $records[] = $record;
        });

        $this->assertEquals($report->getNumRecords(), count($records));
        $this->assertEquals($report->getNumRecords(),0);
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
        $report
            ->setReportDate(new \DateTime("2019-04-27 00:00:00"))
            ->setReportStartDate(new \DateTime("2019-05-02 00:00:00"))
            ->setReportEndDate(new \DateTime("2019-05-02 00:00:00"));

        $report->writeRecords("/tmp/output-eiep13a.txt", []);

        $output = file_get_contents("/tmp/output-eiep13a.txt");

        $this->assertEquals("HDR,ICPCONS,1.1,,,,27/04/2019,,00000000,02/05/2019,02/05/2019" . PHP_EOL, $output);
    }

    public function testWriteEmptyRecords()
    {
        $report = new Report();
        $report
            ->setReportDate(new \DateTime("2019-04-27 00:00:00"))
            ->setReportStartDate(new \DateTime("2019-05-02 00:00:00"))
            ->setReportEndDate(new \DateTime("2019-05-02 00:00:00"));

        $currentDate = date(DetailRecord::TIME_FORMAT);

        $records = [
            new DetailRecord(),
            new DetailRecord()
        ];

        $report->writeRecords("/tmp/output-eiep13a.txt", $records);
        $output = file_get_contents("/tmp/output-eiep13a.txt");

        $expected = <<<CSV
HDR,ICPCONS,1.1,,,,27/04/2019,,00000002,02/05/2019,02/05/2019
DET,,,,,,,,,"$currentDate","$currentDate",,,
DET,,,,,,,,,"$currentDate","$currentDate",,,

CSV;

        $this->assertEquals($expected, $output);
        $this->assertEquals($report->getNumRecords(), 2);
    }

    public function testWriteCompleteRecords()
    {
        $report = new Report();
        $report
            ->setReportDate(new \DateTime("2019-04-27 00:00:00"))
            ->setReportStartDate(new \DateTime("2019-05-02 00:00:00"))
            ->setReportEndDate(new \DateTime("2019-05-02 00:00:00"))
            ->setNumRecords(1)
            ->setSender("Company")
            ->setRecipient("Recipient")
            ->setOnBehalfOf("Behalf")
            ->setIdentifier("1234567890");

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

        $records = [
            $record,
            $record,
            $record
        ];

        $report->writeRecords("/tmp/output-eiep13a.txt", $records);
        $output = file_get_contents("/tmp/output-eiep13a.txt");

        $expected = <<<CSV
HDR,ICPCONS,1.1,Comp,Beha,Reci,27/04/2019,1234567890,00000003,02/05/2019,02/05/2019
DET,AAAAAAAAAAAAAAAAAAAA,1234567890,000,NZST,CCCCCCCCCCCCCCCCCCCCCCCCCCCCCC,X,000000,000000,"27/04/2019 00:00:00","27/04/2019 00:00:00",RD,1,2
DET,AAAAAAAAAAAAAAAAAAAA,1234567890,000,NZST,CCCCCCCCCCCCCCCCCCCCCCCCCCCCCC,X,000000,000000,"27/04/2019 00:00:00","27/04/2019 00:00:00",RD,1,2
DET,AAAAAAAAAAAAAAAAAAAA,1234567890,000,NZST,CCCCCCCCCCCCCCCCCCCCCCCCCCCCCC,X,000000,000000,"27/04/2019 00:00:00","27/04/2019 00:00:00",RD,1,2

CSV;

        $this->assertEquals($expected, $output);
    }

    public function testWriteStream()
    {
        $report = new Report();
        $report
            ->setReportDate(new \DateTime("2019-04-27 00:00:00"))
            ->setReportStartDate(new \DateTime("2019-05-02 00:00:00"))
            ->setReportEndDate(new \DateTime("2019-05-02 00:00:00"))
            ->setNumRecords(3)
            ->setSender("Company")
            ->setRecipient("Recipient")
            ->setOnBehalfOf("Behalf")
            ->setIdentifier("1234567890");

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
HDR,ICPCONS,1.1,Comp,Beha,Reci,27/04/2019,1234567890,00000003,02/05/2019,02/05/2019
DET,AAAAAAAAAAAAAAAAAAAA,1234567890,000,NZST,CCCCCCCCCCCCCCCCCCCCCCCCCCCCCC,X,000000,000000,"27/04/2019 00:00:00","27/04/2019 00:00:00",RD,1,2
DET,AAAAAAAAAAAAAAAAAAAA,1234567890,000,NZST,CCCCCCCCCCCCCCCCCCCCCCCCCCCCCC,X,000000,000000,"27/04/2019 00:00:00","27/04/2019 00:00:00",RD,1,2
DET,AAAAAAAAAAAAAAAAAAAA,1234567890,000,NZST,CCCCCCCCCCCCCCCCCCCCCCCCCCCCCC,X,000000,000000,"27/04/2019 00:00:00","27/04/2019 00:00:00",RD,1,2

CSV;

        $this->assertEquals($expected, $output);
    }
}
