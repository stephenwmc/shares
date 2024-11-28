<?php

namespace shares;

class newDividendClass
{
    public $shareCode;
    public $shareName;
    public $sharePrice;
    public $dividend;
    public $divDate;
    public $yield;

    public function __construct()
    {

        $monthago = date('Y-m-d H:i:s', mktime() - (60 * 60 * 24 * 31));
        $shareDetail = new sqlDataset("select * from share_detail where dividend_date_changed < '$monthago' or dividend_date_changed is null");
        $shareDetail->read();
        $this->shareCode = $shareDetail->share_code;
        $this->shareName = $shareDetail->share_name;
        $this->sharePrice = $shareDetail->buy_price;
    }

    public function display()
    {
        echo "<h2>Share $this->shareCode $this->shareName fetching info...</h2>";
        $yahooURL = "http://uk.finance.yahoo.com/q?s=$this->shareCode.L";
        $yahooPage = fopen($yahooURL, "r ");
        if ($yahooPage) {
            while ($yahooLine = fread($yahooPage, 2048)) {
                $start = strpos($yahooLine, "Dividend:");
                if ($start > 0) {
                    $yahooLine = substr($yahooLine, $start + 9);
                    $end = strpos($yahooLine, "</nobr>");
                    if ($end > 0) {
                        $yahooLine = substr($yahooLine, 0, $end);
                    }
                    $yahooLine = str_replace('</td><td class="yfnc_tabledata1">', '', $yahooLine);
                    $yahooLine = str_replace('<nobr>', ' ,', $yahooLine);
                    $yahooArray = explode(",", $yahooLine);
                    echo "<p>$yahooLine</p>";
                    $this->dividend = $yahooArray[0];
                    $tmpDate = $yahooArray[1];
                    $tmpDate = str_replace("(", "", $tmpDate);
                    $tmpDate = str_replace(")", "", $tmpDate);
                    $tmpDate1 = explode("-", $tmpDate);
                    $tmpDate = $tmpDate1;
                    $this->dividend = str_replace('p', '', $this->dividend);
                    $this->divDate = date('Y-m-d H:i:s', mktime(0, 0, 0, monthLookup($tmpDate[1]), $tmpDate[0], $tmpDate[2] + 2000));
                    //					if (!is_numeric($this->dividend)) {
                    //						$this->dividend = 0;
                    //					}
                    $this->dividend = str_replace("N/A", "0", $this->dividend);
                    $this->yield = number_format(($this->dividend / $this->sharePrice * 100), 3, '.', '');
                    echo "<p>Dividend $this->dividend on $this->divDate yield = $this->yield</p>";
                    $updateShareDetail = new sqlCommand("update share_detail set dividend = '$this->dividend', dividend_date = '$this->divDate', yield = '$this->yield', dividend_date_changed = '" . date('Y-m-d H:i:s') . "' where share_code = '$this->shareCode'");
                    echo "<p>sql = $updateShareDetail->sql </p>";
                    if (!$updateShareDetail) {
                        die('Unable to update dividend details ' . mysqli_error($link));
                    }
                    die('here');
                } else {
                    $updateShareDetail = new sqlCommand("update share_detail set dividend_date_changed = '" . date('Y-m-d H:i:s') . "' where share_code = '$this->shareCode'");
                    if (!$updateShareDetail) {
                        die('Unable to update dividend details ' . mysqli_error($link));
                    }
                }
            }
        } else {
            echo "<p>Unable to open finance page for $yahooURL</p>";
        }
    }

}

function monthLookup($inMonth)
{

    switch ($inMonth) {
        case "Jan":
            return 1;
            break;
        case "Feb":
            return 2;
            break;
        case "Mar":
            return 3;
            break;
        case "Apr":
            return 4;
            break;
        case "May":
            return 5;
            break;
        case "Jun":
            return 6;
            break;
        case "Jul":
            return 7;
            break;
        case "Aug":
            return 8;
            break;
        case "Sep":
            return 9;
            break;
        case "Oct":
            return 10;
            break;
        case "Nov":
            return 11;
            break;
        case "Dec":
            return 12;
            break;
        default:
            return false;
            break;
    }
}

function currency($inVal)
{
    if (is_null($inVal)) {
        return "";
    }
    return number_format($inVal, 2, '.', '');
}

function nz($inNumber)
{

    if (!is_numeric($inNumber)) {
        $outNumber = 0;
    } else {
        $outNumber = $inNumber;
    }
    return $outNumber;
}

function writeLog($inLabel, $inMessage, $shareCode = '', $amount = 0)
{

    $headers = 'From: minimac@mcaleer.org.uk' . "\r\n" .
    'Reply-To: minimac@mcaleer.org.uk' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
    mail('stephen.mcaleer@gmail.com', $inLabel, $inMessage, $headers);
    $sql = "insert into share_activity set activity_date = now(), message = '{$inLabel}:{$inMessage}', share_code='{$shareCode}',amount={$amount}";
    $insertActivity = new sqlCommand($sql);
    $tidyActivity = new sqlCommand("delete from share_activity where datediff(activity_date, curdate()) < -90");
}

function getCandlestick($open, $close, $low, $high)
{
    $candlestickType = "";
    if (
        (
            (
                ($high - $low) > 3 * ($open - $close)
            ) && (
                ($close - $low) / (.001 + $high - $low) > 0.6
            ) && (
                ($open - $low) / (.001 + $high - $low) > 0.6
            )
        )) {
        $candlestickType = "Hammer";
    } elseif (
        (
            (
                ($high - $low) > 4 * ($open - $close)
            ) && (
                ($close - $low) / (.001 + $high - $low) >= 0.75
            ) && (
                ($open - $low) / (.001 + $high - $low) >= .075
            )
        )) {
        $candlestickType = "White Hanging Man";
    } elseif (
        (
            (
                ($high - $low) > 4 * ($close - $open)
            ) && (
                ($close - $low) / (.001 + $high - $low) >= 0.75
            ) && (
                ($open - $low) / (.001 + $high - $low) >= .075
            )
        )) {
        $candlestickType = "Black Hanging Man";
    } elseif (
        (
            (
                ($high - $low) > 4 * ($open - $close)
            ) && (
                ($high - $close) / (.001 + $high - $low) >= 0.75
            ) && (
                ($high - $open) / (.001 + $high - $low) >= 0.75
            )
        )) {
        $candlestickType = "Shooting star";
    } elseif (
        (
            (
                ($high - $low) > 3 * ($open - $close)
            ) && (
                ($high - $close) / (.001 + $high - $low) > 0.6
            ) && (
                ($high - $open) / (.001 + $high - $low) > 0.6
            )
        )) {
        $candlestickType = "Inverted hammer";
    } elseif (abs($open - $close) <= .1 * ($high - $low) && abs($open + $close - $high - $low) / 2 <= .03 * ($high - $low) && $high > $low) {
        $candlestickType = "Spinning top";
    } elseif ($high == $close && $open == $low) {
        $candlestickType = "White marubozu";
    } elseif ($low == $close && $open == $high) {
        $candlestickType = "Black marubozu";
    } elseif ($open == $close) {
        $candlestickType = "Doji";
    } elseif ($close == $low && $high > $open) {
        $candlestickType = "Shaven Bottom";
    } elseif ($close == $high && $open > $low) {
        $candlestickType = "Shaven Head";
    } else {
        $candlestickType = "Open $open close $close high $high low $low";
    }
    return $candlestickType;
}

function displayDate(String $inString = '1970-01-01')
{
    if (substr($inString, 0, 10) == date('Y-m-d')) {
        return substr($inString, 11);
    } else {
        return substr($inString, 8, 2) . '-' . substr($inString, 5, 2) . '-' . substr($inString, 0, 4);
    }
}
