<?php

namespace shares;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function readJsonFile($inResult, $shareCode = '')
{

    if (!$inResult) {
        echo "Not valid file!!!\n";
    } else {
        $urlDetails = json_decode($inResult, true);
        $summaryProfile = $urlDetails['quoteSummary']['result'][0]['summaryProfile'];
        $quoteType = $urlDetails['quoteSummary']['result'][0]['quoteType'];
        $price = $urlDetails['quoteSummary']['result'][0]['price'];
        $financialData = $urlDetails['quoteSummary']['result'][0]['financialData'];
//			$urlLine = trim($urlLine);
//			echo "*$urlLine*\n";
//			$urlLine = str_replace('"', "",$urlLine);
//			$urlDetails = explode(",",$urlLine) or die('Bad csv format');
        if (isset($quoteType['symbol'])) {
//            echo "setting sharecode from $shareCode to " . $quoteType['symbol'];
          $shareCode = $quoteType['symbol'];
        }
        $shareCode = str_replace('.L','',$shareCode);
        $myShare = new myShareClass($shareCode);
        if (isset($quoteType['longName'])) {
        $myShare->shareName = $quoteType['longName'];
        }
//        echo "Myshare code " . $myShare->shareCode;
        if ($myShare->buyPrice != $price['regularMarketPrice']['raw']) {
            $myShare->changed = true;
        }
        if (isset($price['regularMarketPrice']['raw'])) {
            $buyPrice = $price['regularMarketPrice']['raw'];
        } elseif (isset($financialData['currentPrice']['raw'])) {
            $buyPrice = $financialData['currentPrice']['raw'];
        } else {
            echo "No price found \n";
            $buyPrice = 0;
        }
//        $myShare->forwardEps = $urlDetails['quoteSummary']['result'][0]['defaultKeyStatistics']['forwardEps']['fmt'] ? $urlDetails['quoteSummary']['result'][0]['defaultKeyStatistics']['forwardEps']['fmt'] : 0;
        $myShare->lastDividendAmount = isset($urlDetails['quoteSummary']['result'][0]['defaultKeyStatistics']['lastDividendValue']['fmt']) ? $urlDetails['quoteSummary']['result'][0]['defaultKeyStatistics']['lastDividendValue']['fmt'] : 0;
        $myShare->exDividendDate = isset($urlDetails['quoteSummary']['result'][0]['calendarEvents']['exDividendDate']['fmt']) ? $urlDetails['quoteSummary']['result'][0]['calendarEvents']['exDividendDate']['fmt'] : '1970-01-01';

        $myShare->buyPrice = $buyPrice;
        if (isset($price['regularMarketTime'])) {
            $shareDateIn = $price['regularMarketTime'];
//            echo "Date in is $shareDateIn aka " . date('Y-m-d H:i:s', $shareDateIn);
        } else {
            $shareDateIn = time();
        }
        $myShare->lastQuantity = $price['regularMarketVolume']['raw'];
        // @TODO use max and min data if available
//        $myShare->min= $price['regularMarketDayLow']['raw'];
//        $myShare->max = $price['regularMarketDayHigh']['raw'];
        $eps = $financialData['revenuePerShare']['raw'];
        $peRatio = $financialData['currentRatio']['raw'];
        $myShare->yield = 0; // @TODO find what this really is $urlDetails[8];
        $myShare->sellPrice = $buyPrice; // @TODO is there a better price $urlDetails[10];
        if (isset($summaryProfile['sector'])) {
            $myShare->marketSector = $summaryProfile['sector'];
        } else {
            if (isset($quoteType['quoteType'])) {
            $myShare->marketSector = $quoteType['quoteType'];                
            } else {
                $myShare->marketSector = 'UNKNOWN';
            }
        }
        if ($myShare->dateChanged == 0) 
            $myShare->dateChanged = date('Y-m-d H:i:s' ,0);
        $myShare->newDateChanged = date('Y-m-d H:i:s', $shareDateIn);
//  				echo "date changed $myShare->newDateChanged to $myShare->dateChanged \n";
        if ($myShare->buyPrice != 0 && $myShare->newDateChanged > $myShare->dateChanged) {
            $myShare->dateChanged = $myShare->newDateChanged;
//    	    		echo "Reading $shareCode, $myShare->buyPrice at $myShare->dateChanged \n";
//        			$myShare->lastBuyPrice = $myShare->buyPrice;
            if (!is_numeric($myShare->lastBuyPrice)) {
                $myShare->lastBuyPrice = 0;
            }
            $myShare->previousQuantity = $myShare->lastMovementQuantity;
            if ($myShare->previousQuantity == $lastQuantity || $lastQuantity == 0) {
                $myShare->previousQuantity = $myShare->previousMovementQuantity;
                $myShare->lastQuantity = $myShare->lastMovementQuantity;
            }
            $lastMovement = "";
            if ($myShare->buyPrice > $myShare->lastBuyPrice) {
                $lastMovement = "UP";
            } elseif ($myShare->buyPrice < $myShare->lastBuyPrice) {
                $lastMovement = "DOWN";
            }
            if ($lastMovement != $myShare->lastMovement && $lastMovement != "") {
                $previousMovement = $myShare->lastMovement;
                $previousMovementStreak = $myShare->lastMovementStreak;
                $myShare->lastMovementStreak = 1;
                $myShare->lastMovement = $lastMovement;
            } elseif ($lastMovement == $myShare->lastMovement && $myShare->lastMovement != "") {
                $myShare->lastMovementStreak = $myShare->lastMovementStreak + 1;
            }
//        			echo "\n movement *" . $lastMovement . "* \n";
            $myShare->update();
        }
        $shareHistory = new sqlDataset("select max(buy_price) as inmax from share_history where share_code = '$myShare->shareCode' group by share_code");
        if ($shareHistory->read()) {
            $inMax = $shareHistory->inmax;
        } else {
            echo "no history found " . $shareHistory->sql;
            $inMax = $myShare->max;
        }
        if (($inMax * 1.5) < $myShare->max) {
            echo "$shareCode - $myShare->shareName : Check max value $inMax against " . $myShare->max;
            $myShare->max = $inMax;
        } elseif (($inMax / 1.5) > $myShare->max) {
            echo "$shareCode - $myShare->shareName : Check max value $inMax against " . $myShare->max;
            $myShare->max = $inMax;
        }
//				echo "\n\nsetting max to $myShare->max \n";
        $myShare->update();
    }
}
