[![Build Status](https://travis-ci.org/our-energy/eiep.svg?branch=master)](https://travis-ci.org/our-energy/eiep)
[![Latest Stable Version](https://poser.pugx.org/ourenergy/eiep/v/stable?format=flat)](https://packagist.org/packages/ourenergy/eiep)

# Electricity Information Exchange Protocol (EIEP)

A PHP library for working with the Electricity Authority's EIEP data files. Supports PHP 7.1+.

Uses [league/csv](https://github.com/thephpleague/csv) for reading and writing CSV files.

Currently supported protocols and versions;

| Protocol  | Version(s) |
| ------------- | ------------- |
| EIEP3  | 10.0  |
| EIEP13a  | 1.1  |

## Installation

```
composer require ourenergy/eiep
```

## Reading from a file

```php
use Eiep\Eiep3\Report;
use Eiep\Eiep3\DetailRecord;

$report = new Report();

$report->streamFromFile("eiep3.csv", function(DetailRecord $record)  {
    echo $record->getActiveEnergy() . PHP_EOL;
});
    
```

## Working with an existing stream

```php
use Eiep\Eiep3\Report;
use Eiep\Eiep3\DetailRecord;

$report = new Report();

$handle = fopen("eiep3.csv", "r");

$report->readFromStream($handle, function(DetailRecord $record)  {
    echo $record->getActiveEnergy() . PHP_EOL;
});
    
```

## Writing a new report

```php
use Eiep\Eiep3\Report;
use Eiep\Eiep3\DetailRecord;

// Create the report
$report = new Report();
$report->setReportDate(new \DateTime("2019-01-01 00:00:00"));
$report->setUtilityType(Report::UTILITY_TYPE_ELECTRICITY);
$report->setFileStatus(Report::FILE_STATUS_REPLACEMENT);
$report->setNumRecords(1);

// Create records
$record = new DetailRecord();

$record
    ->setIcpIdentifier("1234567890")
    ->setStreamIdentifier("ABCDEFG")
    ->setReadingType(DetailRecord::READING_TYPE_FINAL)
    ->setDate(new \DateTime("2019-01-01 00:00:00"))
    ->setTradingPeriod(48)
    ->setActiveEnergy(1)
    ->setReactiveEnergy(2)
    ->setApparentEnergy(3)
    ->setFlowDirection(DetailRecord::FLOW_DIRECTION_EXTRACT);
    
// Write everything to a file
$records = [
    $record
];

$report->writeRecords("eiep3.csv", $records);

```

## Creating a write stream

```php
use Eiep\Eiep3\Report;
use Eiep\Eiep3\DetailRecord;

// ... prepare your report and records as above ...

// Create a writer
$writer = $report->createWriter("eiep3.csv");

// Write one record
$writer->insertOne($record->toArray());

// Write multiple records
$rows = array_map(function (DetailRecord $record) {
    return $record->toArray();
}, $records);

$writer->insertAll($rows);

```
