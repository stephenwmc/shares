<?php

namespace shares;

require 'vendor/autoload.php';

use GuzzleHttp\Client;

require_once "myClasses.php";

require "readJsonFile.php";

require "classes/shareClass.php";

set_time_limit(0);

$glbMinScore = -10000;
$glbAmountToBuy = 300;

$baseUrl = "https://query1.finance.yahoo.com/v10/finance/";
$crumb = "GAWD46RIuHv";
$cookie = "d=AQABBAbCo2ACEO9vbpbIqj2OZGlC0NXXhjwFEgABCAHJlmTDZPbPb2UB9qMAAAcIBsKjYNXXhjw&S=AQAAAvyQtCUSWva9GymtmBTpbIc";

//
// functions
//

function getScore($inScore)
{

    global $lastShare, $lastScore;

    if ($inScore == $lastShare) {
        return ($lastScore);
    } else {
        $getScore = 0;
        $shareDetail = new sqlDataset("select share_code, score from share_detail where share_code = '$inScore'");
        while ($shareDetail->read()) {
            $getScore = $getScore + $shareDetail->score;
        }
        $recommendations = new sqlDataset("select share_code, sum(score) as t_score from recommendations where share_code = '$inScore' group by share_code");
        while ($recommendations->read()) {
            $getScore = $getScore + $recommendations->t_score;
            $updateShareDetail = new sqlCommand("update share_detail set rec_score = $recommendations->t_score where share_Code = '$inScore'");
        }
        return ($getScore);
        $lastShare = $inScore;
        $lastScore = $getScore;
    }
}

function trimJs($inString)
{
    $scriptTagPos = strpos($inString, "<script");
    while ($scriptTagPos) {
        $endTagPos = strpos($inString, "</script>", $scriptTagPos);
        $inString = substr($inString, 0, $scriptTagPos - 1) . substr($inString, $endTagPos + 9);
        $scriptTagPos = strpos($inString, "<script");
    }
    return $inString;
}

function parseFile($inUrl)
{
    $updateAccountDetails = new sqlCommand("update account_details set update_status = 'Parsing $inUrl'");
    $fgc = file_get_contents($inUrl);
    $body = strpos($fgc, "<body");
    $fgc = substr($fgc, $body);
    $fgc = trimJs($fgc);
    $lsePos = strpos($fgc, "LSE:");
    $aPos = strpos($fgc, "/quote/");
    echo "opening $inUrl lsePos $lsePos aPos $aPos \n";
    while ($lsePos || $aPos) {
        if (($lsePos < $aPos && $lsePos) || !$aPos) {
            $newRecLine = substr($fgc, $lsePos - 5, 80);
            $fgc = substr($fgc, $lsePos + 5);
            //            echo "Share ref found in line " . $newRecLine . "\n";
            $startLse = strpos($newRecLine, "LSE:");
            while ($startLse) {
                $shareCode = substr($newRecLine, $startLse, 9);
                echo "found LSE : **" . $shareCode . "** ";
                $shareCode = str_replace('LSE:', '', $shareCode);
                $shareCode = trim(preg_replace("/[^A-Za-z0-9 ]/", "", $shareCode));
                readShare($shareCode);
                $newRecLine = substr($newRecLine, $startLse + 4);
                $startLse = strpos($newRecLine, "LSE:");
            }
        } else {
            $newRecLine = substr($fgc, $aPos + 7, 80);
            $fgc = substr($fgc, $aPos + 10);
            $endQ = strpos($newRecLine, "\"");
            $shareCode = strtoupper(substr($newRecLine, 0, strpos($newRecLine, "\"")));
            echo "Checking $shareCode \n";
            if (trim($shareCode) != "" && strpos($shareCode, '.L')) {
                $shareCode = substr($shareCode, 0, strpos($shareCode, ".L"));
                $recShare = new myShareClass($shareCode);
                readShare($shareCode);
            }
        }
        $lsePos = strpos($fgc, "LSE:");
        $aPos = strpos($fgc, "/quote/");
        //        echo "chk $inUrl lsePos $lsePos aPos $aPos \n";
    }
}

function parseFiles($inResult)
{
    $inDte = date('ymd');
    $inDte2 = date('dmY');
    $inYr = date('y');
    $htmlLine = file_get_contents($inResult);
    $htmlLine = trimJs($htmlLine);
    $found = true;
    while ($found) {
        //        echo "\n" . strlen($htmlLine) . "\n";
        $startTagPos = strpos($htmlLine, "<a ");
        $endTagPos = strpos($htmlLine, "</a>", $startTagPos);
        if ($startTagPos && $endTagPos) {
            $aTag = substr($htmlLine, $startTagPos, $endTagPos);
            //            echo "start pos $startTagPos end $endTagPos $aTag \n ";
            $htmlLine = substr($htmlLine, $endTagPos + 4);
            $hrefTag = strpos($aTag, "href=");
            if ($hrefTag) {
                $aTag = substr($aTag, $hrefTag + 6);
                $hrefEndTag = strpos($aTag, "\"", 1);
                $aTag = substr($aTag, 0, $hrefEndTag);
                if (substr($aTag, 0, 4) != 'http') {
                    $aTag = "https://uk.finance.yahoo.com" . $aTag;
                }
                if ($aTag != 'https://uk.yahoo.com' && $aTag != "https://uk.finance.yahoo.com") {
                    parseFile($aTag);
                }
            }
        } else {
            $found = false;
        }
    }
}

function readShare($inShare)
{
    global $totalShares, $shareCount, $crumb, $cookie;

    $shareString = $inShare . ".L";
    $url = "https://query1.finance.yahoo.com/v10/finance/quoteSummary/{$shareString}?modules=price,summaryProfile,quoteType,calendarEvents,defaultKeyStatistics,financialData&crumb={$crumb}";
    echo "Reading $url\n";

    try {
        $client = new Client(
            [
                'headers' => [
                    'Cookie' => "A1=$cookie",
                    'User-Agent' => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36"
                ]
            ]
        );
        $response = $client->get($url);
        $updateStatus = "Updating shares, record $shareCount of $totalShares ($shareString)";
        $updateAccountDetails = new sqlCommand("update account_details set update_status = '$updateStatus'");
            $urlResult = $response->getBody()->getContents();
        readJsonFile($urlResult, $inShare);
    } catch (\Exception $e) {
        echo "Failed to get doc\n\n";
        var_dump($e->getMessage());
    }
}

//
// main code here
//

$db_user = 'shares';
$db_pass = 'flarble';
$db_host = 'localhost';
$db_name = 'shares';

require "sharesDb.php";

echo date('Y-m-d H:i:s') . " **STARTING SHARE MONITOR**\n";

$shareDetail = new sqlDataset("select * from share_detail order by share_code");
echo "Refreshing scores ...\n";
while ($shareDetail->read()) {
    $tmpScore = getScore($shareDetail->share_code);
}
echo "Refresh complete\n";
$accountDetails = new sqlDataset("select * from account_details");
$accountDetails->read();
$globalFee = $accountDetails->trading_fee;
$buyFee = $globalFee;
$amountToBuy = floor($accountDetails->account_balance - $buyFee - 0.5);
if ($amountToBuy <= $buyFee || $amountToBuy > $glbAmountToBuy) {
    $amountToBuy = $glbAmountToBuy;
}

echo "Getting recommendations\n";
$url = "https://uk.finance.yahoo.com/topic/news";
parseFiles($url);
$url = "https://uk.finance.yahoo.com/most-active";
parseFile($url);

// read share prices one by one
touch("getShares.run");
$shareCount = 0;
$shareDetail = new sqlDataset("select sd.share_code from share_detail sd inner join shares_owned o on o.share_code = sd.share_code");
echo $shareDetail->rowCount() . " owned shares to read\n";
$shareString = "";
$plus = "";
$totalShares = $shareDetail->rowCount();
while ($shareDetail->read()) {
    $shareCount++;
    readShare($shareDetail->share_code);
}
$shareDetail = new sqlDataset("select * from share_detail where date_changed  < (now() - interval 1 hour) order by date_changed,share_code");
echo $shareDetail->rowCount() . " shares to read\n";
$shareString = "";
$plus = "";
$totalShares = $shareDetail->rowCount();
while ($shareDetail->read()) {
    $shareCount++;
    readShare($shareDetail->share_code);
}
// housekeep
echo date('Y-m-d H:i:s') . "\n\nHousekeeping\n";
$updateAccountDetails = new sqlCommand("update account_details set update_status = 'Housekeeping'");
$updateShareDetail = new sqlCommand("update share_detail set min = buy_price where buy_price < min and min is not null") or die('Unable to execute set min query ' . mysqli_error());
$updateShareDetail = new sqlCommand("update share_detail set market_sector = 'DELISTED', score = 0 where date_changed <= '" . date('Y-m-d', time() - (30 * 24 * 60 * 60)) . "'") or die('Unable to execute delisted query ' . mysqli_error());
$sql = "delete from share_detail where date_changed <= '" . date('Y-m-d', time() - (30 * 24 * 60 * 60)) . "'";
$result = new sqlCommand($sql) or die('Unable to execute query ' . mysqli_error($link));
$sql = "update share_detail set market_sector = null where market_sector = 'DELISTED' and date_changed >= '" . date('Y-m-d', time() - (24 * 60 * 60)) . "'";
$result = new sqlCommand($sql) or die('Unable to execute query ' . mysqli_error($link));
$sql = "delete from recommendations where rec_date < '" . date('Y-m-d', time() - (365 * 24 * 60 * 60)) . "'";
$result = new sqlCommand($sql) or die('Unable to execute query ' . mysqli_error($link));
$sql = "delete from share_history where date_changed <= '" . date('Y-m-d', time() - (365 * 24 * 60 * 60)) . "'";
$result = new sqlCommand($sql) or die('Unable to execute query ' . mysqli_error($link));
$updateAccountDetails = new sqlCommand("update account_details set update_status = '{$shareCount} Shares updated at " . date('H:i:s l jS') . "'");
unlink("getShares.run");
echo date('Y-m-d H:i:s') . "\n\nAll Done\n";
