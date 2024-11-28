<?php

namespace shares;

require_once "abstractModel.php";
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class shareDetail extends abstractModel
{

    public $tableName = "share_detail";
    public $lineClass;
    public $shareCode;
    public $shareName;
    public $displayMe;
    public $marketSector;

    function __construct($where)
    {

        //		$this->displayMe = false;
        parent::__construct($where);
    }

    function read()
    {

        global $totLn, $globals;

        $today = date('Y-m-d');
        $accountDetails = new accountDetail();
        $accountDetails->read();

        if (parent::read()) {
            $this->shareCode = $this->share_code;
            $this->shareName = $this->share_name;
            $this->marketSector = $this->market_sector;
            $this->buyPrice = $this->buy_price;
            $this->recDesc = $this->rec_desc;
            $this->dateChanged = $this->date_changed;
            $this->lastQuantity = $this->last_movement_quantity;
            if ($this->buyPrice != 0) {
                $this->maxProfit = (($accountDetails->getShareVal() / $this->buyPrice) * $this->max) - $accountDetails->getShareVal() - ($accountDetails->getTradingFee() * 2);
            } else {
                $this->maxProfit = 0;
            }

            $this->maxProfit = currency($this->maxProfit, 2);
            $sellFlag = false;
            if ($this->maxProfit > 1200) {
                $this->recDesc = "4 star buy";
            } elseif ($this->maxProfit > ($accountDetails->getShareVal() * 2)) {
                $this->recDesc = "Definite buy";
            } elseif ($this->maxProfit > $accountDetails->getShareVal()) {
                $this->recDesc = "x2 Possible";
            } elseif ($this->maxProfit > ($accountDetails->getShareVal() / 5)) {
                $this->recDesc = "Above 2%";
            } elseif ($this->maxProfit > 0) {
                $this->recDesc = "Not enough profit";
                $sellFlag = true;
            } else {
                $this->recDesc = "Do not buy";
                $sellFlag = true;
            }
            if ($this->dateChanged >= $today) {
                $changedToday = true;
            } else {
                $changedToday = false;
                $this->recDesc = "No activity today";
            }
            if ($this->dateChanged >= date('Y-m-d H:i:s', time() - (60 * 30))) {
                $this->dateHighlight = '<b>';
                $this->dateHighlightoff = '</b>';
            } else {
                $this->dateHighlight = '';
                $this->dateHighlightoff = '';
            }
//            if ($this->dateChanged >= '2010-04-19 16:33:00') {
//            if ($this->dateChanged >= date('Y-m-d H:i:s', time() - 3600)) {
//                $buyFlag = true;
//            } else {
//                $buyFlag = false;
//            }
            $this->candleStick1 = "";
            $this->candleStick2 = "";
            $this->candleStick3 = "";
            $this->candleStick = $this->candlestick_type;
            $shareHistoryCandle = new sqlDataset("select share_code, candlestick from share_history where share_code = '$this->share_code' and date_changed < '" . date('Y-m-d') . "' order by date_changed desc");
            if ($shareHistoryCandle->read()) {
                $this->candleStick1 = $shareHistoryCandle->candlestick;
                if ($shareHistoryCandle->read()) {
                    $this->candleStick2 = $shareHistoryCandle->candlestick;
                    if ($shareHistoryCandle->read()) {
                        $this->candleStick3 = $shareHistoryCandle->candlestick;
                    }
                }
            }

            $this->lastMovement = "$this->last_movement ($this->last_movement_streak)";
            $updateShareDetail = new sqlCommand("update share_detail set max_profit = '$this->maxProfit' where share_code = '$this->share_code'");
            if (nz($this->min) != 0 && $this->min != $this->max) {
                $this->maxPercent = currency(($this->buyPrice - $this->min) / ($this->max - $this->min));
            } else {
                $shareHistory = new sqlDataset("select min(buy_price) as minval, max(buy_price) as maxval from share_history where share_code = '$this->share_code'");
                $shareHistory->read();
                if (!is_null($shareHistory->minval)) {
                    $updateShareDetail = new sqlCommand("update share_detail set min = $shareHistory->minval where share_code = '$this->share_code'");
                }
                if (!is_null($shareHistory->maxval)) {
                    $updateShareDetail = new sqlCommand("update share_detail set max = $shareHistory->maxval where share_code = '$this->share_code'");
                }
                $this->maxPercent = "No min value " . $this->min . " vs " . $this->max;
            }
            $sharesOwned = new sqlDataset("select * from shares_owned where share_code = '$this->share_code'");
            if ($sharesOwned->rowCount() > 0) {
                $owned = true;
            } else {
                $owned = false;
            }
            $buyProfitIf = ($accountDetails->getShareVal() / 1.5);
            $buyPercentIf = 0.25;
            $maxProfitIf = -10;
            $maxPercentIf = -1;
            if ($this->maxProfit > $maxProfitIf && $this->maxPercent > $maxPercentIf && $this->maxPercent < 50 && $this->sell_price > 0) {
                $totLn++;
                $this->ln = $totLn;
                if ($this->last_movement == "UP" &&
                        $this->candlestick_type != '' &&
                        $this->candlestick_type != 'Black marubozu' &&
                        $this->candlestick_type != 'Doji' &&
                        $this->candlestick_type != 'Hanging Man' &&
                        $this->maxPercent < $buyPercentIf &&
                        $this->maxProfit > $buyProfitIf && !$this->previousStopLoss()) {
                    if ($globals->rec == "" && $changedToday && $this->marketSector != 'DELISTED' && $this->marketSector != 'ETF' && $this->marketSector != 'EQUITY') {
                        if (!$owned) {
                            $globals->rec = $this->share_code;
                            $globals->recBuyPrice = $this->buyPrice;
                        }
                    }
                    $this->lineClass = "table-success";
                    $this->displayMe = true;
                } else {
                    $this->lineClass = "table-secondary";
                    if ($this->last_movement != "UP") {
                        $this->recDesc = "share down";
                        $this->lineClass = "table-warning";
                    } elseif ($this->candlestick_type == '' ||
                            $this->candlestick_type == 'Black marubozu' ||
                            $this->candlestick_type == 'Doji' ||
                            $this->candlestick_type == 'Hanging Man') {
                        $this->lineClass = "table-warning";
                        $this->recDesc = "bad candlestick";
                    } elseif ($this->maxPercent >= $buyPercentIf) {
                        $this->recDesc = "percent too high";
                        $this->lineClass = "table-warning";
                    } elseif ($this->maxProfit <= $buyProfitIf) {
                        $this->recDesc = "profit too low $buyProfitIf";
                        $this->lineClass = "table-warning";
                    }
                    if ($this->previousStopLoss()) {
                        $this->recDesc = "Previous stop loss";
                        $this->lineClass = "table-danger";
                    }
                    $this->displayMe = true;
                }
                if ($owned) {
                    $this->lineClass = "table-primary";
		    $this->displayMe = true;
                }
                if ($sellFlag) {
                    $this->ln = 0;
                }
                $updateShareDetail = new sqlCommand("update share_detail set grid_position = $this->ln where share_code = '$this->share_code'");
                if (!$updateShareDetail) {
                    die('Unable to update share detail');
                }
            }
            if ($accountDetails->getAccountBalance() > ($accountDetails->getShareVal() + $accountDetails->getTradingFee()) && $globals->rec != "") {
                $this->btnColour = "btn-success";
                $this->disabled = "";
            } else {
                $this->btnColour = "btn-secondary";
                $this->disabled = "disabled='' ";
            }    
            return true;
        }
    }

    function previousStopLoss()
    {
        $shareActivity = new sqlDataset("select * from share_activity where share_code = '{$this->shareCode}' and amount < 0");
        if ($shareActivity->read()) {
            return true;
        }
        return false;
    }

    function getCandleStickCss($candleStick)
    {

        switch ($candleStick) {
            case "White marubozu":
            case "White Hanging Man":
            case "Shaven top":
                return 'table-success';
            case "Black marubozu":
            case "Black Hanging Man":
            case "Hanging Man":
            case "Hammer":
            case "Shaven Bottom":
                return 'table-danger';
            case "Doji":
            case "Shooting star":
                return 'table-primary';
            default:
                return 'table-secondary';
        }
    }

    function display()
    {
    if ($this->displayMe) {
            $this->shareLink = "https://uk.finance.yahoo.com/quote/" . $this->share_code . ".L/";
            echo "
		<tr id='".$this->share_code."'>
            <td class='$this->lineClass' scope='row'>
			    $this->ln
            </td>
            <td class='$this->lineClass'>
                <button class='btn btn-sm {$this->btnColour}' {$this->disabled} onClick=\"buyShare('{$this->share_code}')\">Buy</button>
            </td>
			<td class='$this->lineClass'><a href='$this->shareLink' target='_blank'>$this->share_code</a></td>
			<td class='$this->lineClass'>$this->shareName</td>
                        <td class='$this->lineClass'>$this->marketSector</td>
			<td class='$this->lineClass'>$this->buyPrice</td>
                        <td class='$this->lineClass'>$this->lastQuantity</td>
			<td class='$this->lineClass'>$this->recDesc</td>
			<td class='$this->lineClass'>$this->max</td>
			<td class='$this->lineClass'>$this->maxProfit</td>
			<td class='$this->lineClass'>$this->dateHighlight" . displayDate($this->dateChanged) . "$this->dateHighlightoff</td>
			<td class='$this->lineClass'>$this->lastMovement</td>
			<td class='$this->lineClass'>$this->maxPercent</td>
                        <td class='" . $this->getCandleStickCss($this->candleStick) . "'>$this->candleStick</td>
                        <td class='" . $this->getCandleStickCss($this->candleStick1) . "'>$this->candleStick1</td>
                        <td class='" . $this->getCandleStickCss($this->candleStick2) . "'>$this->candleStick2</td>
                        <td class='" . $this->getCandleStickCss($this->candleStick3) . "'>$this->candleStick3</td>
		</tr>";
        }
    }

}

function buyShare($inShare)
{

    global $globals;

    $accountDetails = new accountDetail();
    $accountDetails->read();

    if (date('H') >= 18 || date('H') < 9) {
        return;
    }
    echo "<p>Buying $inShare</p>";
    $recQuantity = floor(($accountDetails->getShareVal() * 100) / $globals->recBuyPrice);
    $insertSharesOwned = new sqlCommand("insert into shares_owned set share_code = '$inShare', date_bought = '" . date('Y-m-d H:i:s') . "', quantity = $recQuantity, buy_price = " . $globals->recBuyPrice . ", buy_fee = " . $accountDetails->getTradingFee() . ", share_type = 'PAPER'");
    $message = "Share " . $globals->rec . " bought at " . $globals->recBuyPrice . ".";
    writeLog('Share bought', $message, $inShare);
    $accountBalance = $accountDetails->getAccountBalance() - ($recQuantity * ($globals->recBuyPrice / 100)) - $accountDetails->getTradingFee();
    $accountDetails->setAccountBalance($accountBalance);
}
