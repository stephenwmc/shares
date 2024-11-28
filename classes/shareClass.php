<?php

namespace shares;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class myShareClass
{

    public $shareCode;
    public $shareName;
    public $buyPrice;
    public $yield;
    public $dateChanged;
    public $lastBuyPrice;
    public $lastQuantity;
    public $previousQuantity;
    public $max;
    public $tmpDot;
    public $changed;
    public $lastMovement;
    public $exDividendDate;
    public $lastDividendAmount;
    public $forwardEps;

    function __construct($inShare)
    {

        $inShare = str_replace(".L", "", $inShare);
        $this->shareCode = $inShare;
        if (strlen($this->shareCode) == 2) {
            $this->tmpDot = ".";
        } else {
            $this->tmpDot = "";
        }
        $this->changed = false;
        $this->xx = "";
        if (strlen($this->shareCode) > 4) {
            $this->shareCode = substr($this->shareCode, 0, 4);
        }
        $shareDetail = new sqlDataset("select * from share_detail where share_code = '$this->shareCode'");
        if ($shareDetail->read()) {
            $this->shareName = $shareDetail->share_name;
            $this->buyPrice = $shareDetail->buy_price;
            $this->yield = $shareDetail->yield;
            $this->dateChanged = $shareDetail->date_changed;
            $this->lastBuyPrice = nz($shareDetail->buy_price);
            $this->lastMovement = $shareDetail->last_movement;
            $this->lastMovementStreak = $shareDetail->last_movement_streak;
            $this->max = nz($shareDetail->max);
        } else {
            $insertShareDetail = new sqlCommand("insert into share_detail set share_code = '$this->shareCode', date_added = '" . date('Y-m-d H:i:s') . "'");
            echo "New share $this->shareCode, $this->buyPrice at $this->dateChanged \n";
        }
//        echo "construct sharecode " . $this->shareCode;
    }

    function update()
    {
        
        global $link;

        if (is_numeric($this->buyPrice)) {
            if (!is_numeric($this->lastQuantity)) {
                $this->lastQuantity = 0;
            }
            if (!is_numeric($this->previousQuantity)) {
                $this->previousQuantity = 0;
            }
            $this->shareName = str_replace("'", "''", $this->shareName);
            $sql = "update share_detail set buy_price = '$this->buyPrice', 
								sell_price = '$this->sellPrice', 
								share_name = '$this->shareName',
								yield = '$this->yield',
								date_changed = '$this->dateChanged', 
								date_checked = '" . date('Y-m-d H:i:s') . "', 
								last_buy_price = " . nz($this->lastBuyPrice) . ", 
								last_movement_quantity = " . $this->lastQuantity . ", 
								last_movement = '" . $this->lastMovement . "',
								last_movement_streak = '" . nz($this->lastMovementStreak) . "', 
								previous_movement_quantity = $this->previousQuantity,
                                ex_dividend_date = '" . $this->exDividendDate . "',
                                last_dividend_amount = '" . $this->lastDividendAmount . "',
								max = " . nz($this->max) . " ,
                                market_sector = '$this->marketSector'
				    where share_code = '$this->shareCode'";
            $uresult = mysqli_query($link,$sql) or die('Unable to update share detail ' . $sql . ' : ' . mysqli_error($link));
        }
        if ($this->changed) {
            $this->doHistory();
        }
    }

    function doHistory()
    {

        if ($this->shareCode != "FTSE" && $this->shareCode != "") {
            if ($this->buyPrice == 'N/A') {
                $this->buyPrice = 0;
            }
            $shortDate = substr($this->dateChanged, 0, 10);
//            echo "short date $shortDate from " . $this->dateChanged . " \n";
            $shareHistory = new sqlDataset("select * from share_history where share_code = '$this->shareCode' and date_changed = '$shortDate'");
            if ($shareHistory->read()) {
                if ($this->buyPrice < $shareHistory->low_price) {
                    $shareHistory->low_price = nz($this->buyPrice);
                } elseif ($this->buyPrice > $shareHistory->high_price) {
                    $shareHistory->high_price = nz($this->buyPrice);
                }
                $this->candlestick = getCandleStick($shareHistory->open_price, $shareHistory->close_price, $shareHistory->low_price, $shareHistory->high_price);
                // update figures
                $updateHistory = new sqlCommand("update share_history set close_price={$this->buyPrice},low_price=".nz($shareHistory->low_price).",high_price=".nz($shareHistory->high_price).",candlestick = '$this->candlestick' where share_code = '{$this->shareCode}' and date_changed = '$shortDate'");
//                echo $updateHistory->sql;
                $updateDetail = new sqlCommand("update share_detail set candlestick_type = '$this->candlestick' where share_code = '" . $this->shareCode . "'");
            } else {
                $addHistory = new sqlCommand("insert into share_history set share_code = '{$this->shareCode}',date_changed = '$shortDate',open_price={$this->buyPrice},close_price={$this->buyPrice},low_price={$this->buyPrice},high_price={$this->buyPrice},buy_price={$this->buyPrice}");
//                echo $addHistory->sql;
            }
        }
    }
}