<?php

namespace shares;

require_once "abstractModel.php";
require_once "model/accountDetail.php";
/**
 *
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class sharesOwned extends abstractModel
{
    public $tableName = "shares_owned";
    public $shareCode;
    public $profit;
    public $currentValue;
    public $daysOwned;
    public $dateBought;
    public $quantity;
    public $dividendDate;
    public $shareBuyPrice;

    public function __construct($shareCode)
    {

        global $globals;

        $today = date('Y-m-d');
        $accountDetails = new accountDetail();
        $accountDetails->read();

        $this->sql = "select shares_owned.share_code,
         share_detail.share_name, share_detail.last_movement, 
         share_detail.last_movement_streak, share_detail.min, share_detail.max, shares_owned.buy_price as orig_price, 
         shares_owned.sell_price, share_detail.buy_price as current_price, share_detail.grid_position, shares_owned.quantity as quantity, 
         shares_owned.date_bought, share_detail.date_changed, share_detail.market_sector, shares_owned.share_type,
         share_detail.ex_dividend_date, share_detail.forward_eps,
         (select max(dividend_date) from dividends d where d.share_code = shares_owned.share_code) as dividend_date,
         (select round(sum(dividend_amount),2) from dividends d where d.share_code = shares_owned.share_code and dividend_date >= date_sub(now(),interval 1 year)) as dividend_total
         from shares_owned 
         inner join share_detail on shares_owned.share_code = share_detail.share_code where shares_owned.share_code = '{$shareCode}' 
         order by shares_owned.date_bought";
        parent::__construct();

        if ($this->read()) {
            $this->shareCode = $this->share_code;
            $this->shareName = $this->share_name;
            $this->marketSector = $this->market_sector;
            $this->dateBought = $this->date_bought;
            $this->dateChanged = $this->date_changed;
            $this->dividendDate = $this->dividend_date;
            $this->dividendTotal = $this->dividend_total;
            $this->shareBuyPrice = currency($this->orig_price * $this->quantity / 100);
            $this->shareSellPrice = currency($this->sell_price * $this->quantity / 100);
            $this->currentValue = currency($this->current_price * $this->quantity / 100);
            if ($this->currentValue != 0) {
            $this->yield = (round($this->dividendTotal / $this->currentValue, 2) * 100) . "%";
            } else {
                $this->yield = 0;
            }
            $this->daysOwned = (strtotime($this->dateChanged) - strtotime($this->dateBought)) / 86400;
            if ($this->daysOwned < 6 && $this->shareBuyPrice < 500) {
                $this->sellProfit = 5;
            } elseif ($this->daysOwned < 14) {
                $this->sellProfit = $accountDetails->getSellProfit();
            } elseif ($this->daysOwned <= 21) {
                $this->sellProfit = $accountDetails->getSellProfit() / 2;
            } else {
                $this->sellProfit = 0.01;
            }
            $this->shareType = $this->share_type;
            $this->maxValue = currency($this->max * $this->quantity / 100);
            $this->minValue = currency($this->min * $this->quantity / 100);
            $this->exDividendDate = $this->ex_dividend_date;
            $this->forwardEps = $this->forward_eps;
            $this->currentPrice = $this->current_price / 100;
            $this->profit = currency($this->currentValue - $this->shareBuyPrice - ($accountDetails->getTradingFee() * 2));
            $this->maxProfit = currency($this->maxValue - $this->shareBuyPrice - ($accountDetails->getTradingFee() * 2));
            $this->minProfit = currency($this->minValue - $this->shareBuyPrice - ($accountDetails->getTradingFee() * 2));
            $globals->currentProfit = currency($globals->currentProfit + $this->profit);
            $globals->totalCurrentValue = $globals->totalCurrentValue + $this->currentValue;
            if ($this->currentValue != 0) {
            $this->profitPercent = round((($this->currentValue + $this->dividendTotal) - $this->shareBuyPrice) / $this->shareBuyPrice, 2);
            } else {
                $this->profitPercent = 0;
            }
            if (floatval($this->profit) >= floatval($this->maxProfit)) {
                $boldClass = " font-weight-bold ";
            } else {
                $boldClass = "";
            }
            if ($this->profitPercent > 1) {
                $this->class = "class='table-success $boldClass'";
                $this->lineClass = "class='line1'";
            } elseif ($this->profitPercent > .25) {
                $this->class = "class='table-info $boldClass'";
                $this->lineClass = "class='line1'";
            } elseif ($this->profitPercent >= 0) {
                $this->class = "class='table-primary $boldClass'";
                $this->lineClass = "class='line2'";
            } elseif ($this->profitPercent > -.1) {
                $this->class = "class='table-secondary $boldclass'";
                $this->lineClass = "class='line2'";
            } elseif ($this->profitPercent > -.30) {
                $this->class = "class='table-warning $boldClass'";
                $this->lineClass = "class='line2'";
            } else {
                $this->class = "class='table-danger $boldClass'";
                $this->lineClass = "class='line2'";
            }
            $this->gridPos = $this->grid_position;
            $this->lastMovement = $this->last_movement;
            $this->lastMovementStreak = $this->last_movement_streak;
            if ($this->currentValue > ($this->getIdealAmount() * 2 )) {
                $this->trCode = "table-success";
            } elseif ($this->currentValue > $this->getIdealAmount()) {
                $this->trCode = "table-light";
            } elseif ($this->currentValue < ($this->getIdealAmount() / 2)) {
                $this->trCode = "table-info";
            } else {
                $this->trCode = "table-secondary";
            }
            if (date('H') < 18 && date('H') > 8) {
                // stop loss handled manually now
                //if ($this->profit < -50 && $this->daysOwned > 1) {
                // stop loss
                //    $this->sellShare();
                //}
                if ($this->gridPos == 0) {
                    if ($this->profit >= ($this->sellProfit) && $this->dateChanged >= $today && $this->lastMovement == 'DOWN' && $this->lastMovementStreak > 1) {
                        //$this->sellShare();
                    }
                } elseif ($this->profit > 50 && $this->dateChanged >= $today && $this->lastMovement == 'DOWN' && $this->lastMovementStreak > 2) {
                    //$this->sellShare();
                } elseif ($this->profit > 0 && $this->dateChanged >= $today && $this->daysOwned > 28) {
                    //$this->sellShare();
                }
                if ($this->profit < -20) {
                    $this->btnColour = "btn-danger";
                } elseif ($this->profit < -0) {
                    $this->btnColour = "btn-warning";
                } elseif ($this->profit > 0) {
                    if ($this->gridPos == 0) {
                        $this->btnColour = "btn-success";
                    } else {
                        $this->btnColour = "btn-primary";
                    }
                }
                if ($this->dateChanged < $today) {
                    $disabled = " disabled";
                } else {
                    $disabled = "";
                }
            } else {
                $this->btnColour = "btn-secondary";
                $disabled = " disabled";
            }
            $this->noSellReason = "";
            if (floatval($this->currentPrice) < $accountDetails->account_balance) {
                if ($this->shareType == 'SELLONLY') {
                    $this->noSellReason .= "<button disabled class='btn btn-sm btn-secondary'>Buy</button> "; 
                } else {                     
                    $this->noSellReason .= "<button class='btn btn-sm btn-primary'>Buy</button> "; 
                }
            }
            $this->noSellReason .= "<button class='btn btn-sm {$this->btnColour}' {$disabled} onClick=\"sellShare('{$this->share_code}')\">Sell</button>";
        } else {
            $this->shareCode = $shareCode;
            $this->sellShare();
            return false;
        }
    }

    public function getIdealAmount()
    {
        $accountDetails = new accountDetail();
        $accountDetails->read();
        return $accountDetails->amount_invested / 21;
    }

    public function getDateChanged()
    {
        return displayDate($this->dateChanged);
    }

    public function getDateChangedClass()
    {
        if ($this->dateChanged < date('Y-m-d')) {
            return "class='table-warning'";
        }
        return "";
    }

    public function getDaysOwned()
    {
        if ($this->exDividendDate > $this->dividendDate && $this->exDividendDate > date('Y-m-d',strtotime('-90 days'))) {
            $divDate = new \DateTime($this->exDividendDate);
            return 'Ex Div: ' . $divDate->format('jS M y') . ' ' . floor($this->forwardEps * $this->quantity) . 'p';
        } elseif (!$this->dividendDate) {
            $dateBought = new \DateTime($this->dateBought);
        } else {
            $divDate = new \DateTime($this->dividendDate);
            return 'Div: ' . $divDate->format('jS M y');
        }
        $today = new \DateTime();
        $daysOwned = date_diff($dateBought, $today)->format('%yy %mm %dd');
        return 'Owned: ' . $daysOwned;
    }

    public function getDaysOwnedClass()
    {
        if ($this->exDividendDate >= $this->dividendDate && $this->exDividendDate >= date('Y-m-d')) {
            return "class='table-success'";
        } elseif ($this->exDividendDate > $this->dividendDate && $this->exDividendDate > date('Y-m-d',strtotime('-90 days'))) {
            return "class='table-primary'";
        } elseif ($this->dividendDate) {
            $dateBought = new \DateTime($this->dividendDate);
            $danger = 360;
            $warning = 180;
            $secondary = 90;
        } else {
            $dateBought = new \DateTime($this->dateBought);
            $danger = 90;
            $warning = 30;
            $secondary = 14;
        }
        $today = new \DateTime();
        $daysOwned = date_diff($dateBought, $today)->format('%a');
        if ($daysOwned >= $danger) {
            return "class='table-danger'";
        } elseif ($daysOwned >= $warning) {
            return "class='table-warning'";
        } elseif ($daysOwned >= $secondary) {
            return "class='table-secondary'";
        } elseif ($daysOwned < 2) {
            return "class='table-dark'";
        }
        return "";
    }

    public function getLastMovementSymbol()
    {
        if ($this->lastMovement == 'UP') {
            if ($this->lastMovementStreak < 3) {
                return "&uarr;";
            } elseif ($this->lastMovementStreak < 10) {
                return "&uarr;&uarr;";
            } else {
                return "&uarr;&uarr;&uarr;";
            }
        } elseif ($this->lastMovement == 'DOWN') {
            if ($this->lastMovementStreak < 3) {
                return "&darr;";
            } elseif ($this->lastMovementStreak < 10) {
                return "&darr;&darr;";
            } else {
                return "&uarr;&uarr;&uarr;";
            }
        } else {
            return '=';
        }
    }

    public function dateOld()
    {
        return date('Y-m-d H:i:s', strtotime("-9 months"));
    }

    public function boldOpen()
    {
        if ($this->dividendDate == '') {
            return "";
        }
        if ($this->dividendDate < $this->dateOld()) {
            return "<b><i>";
        }
        return "<b>";
    }

    public function boldClose()
    {
        if ($this->dividendDate == '') {
            return "";
        }
        if ($this->dividendDate < $this->dateOld()) {
            return "</i></b>";
        }
        return "</b>";
    }

    public function dividendClass()
    {
        if ($this->yield >= '5%') {
            return "table-success";
        } elseif ($this->yield >= '3%') {
            return "table-info";
        } elseif ($this->yield >= '1%') {
            return "table-secondary";
        }
        return "";
    }
    public function shareLocked()
    {
        if ($this->shareType == 'SELLONLY') {
            return " <i class='fa-solid fa-lock'></i>";
        } else {
            return "";
        }
    }

    public function display()
    {
        ?>
        <tr>
            <td><a href='#<?= $this->shareCode ?>'><?= $this->shareCode; ?></td>
            <td class="<?= $this->trCode;?>"><?= $this->boldOpen() ?><?= $this->shareName ?><?=$this->shareLocked() ?><?= $this->boldClose() ?></td>
            <td class="<?= $this->trCode;?>"><?= $this->marketSector; ?></td>
            <td align='right' class="<?= $this->trCode;?>"><?= $this->currentValue . "(" . $this->getLastMovementSymbol() . ")"; ?></td>
            <td align='right' <?= $this->class; ?>><?= $this->profit; ?></td>
            <td align='right' class="<?= $this->dividendClass() ?>"><?= $this->boldOpen() ?><?= $this->dividendTotal; ?> <?= $this->yield; ?><?= $this->boldClose() ?></td>
            <td align='center' <?= $this->getDaysOwnedClass(); ?>><?= $this->getDaysOwned(); ?></td>
            <td align='center' <?= $this->getDateChangedClass(); ?>><?= $this->getDateChanged(); ?></td>
            <td align='right'><?= $this->quantity; ?></td>
            <td><?= $this->noSellReason; ?></td>
        </tr>
<?php
    }

    public function sellShare()
    {

        global $globals;

        $accountDetails = new accountDetail();
        $accountDetails->read();

        if (!isset($this->shareCode)) {
            die('Share not set');
        }
        if (date('H') >= 18 || date('H') < 9) {
            return;
        }
        $shares = new sqlDataset("select * from share_detail where share_code = '$this->shareCode'");
        if ($shares->read()) {
            if ($shares->date_changed < date('Y-m-d') . ' 08:00:00') {
                return;
            }
            $sharesOwned = new sqlDataset("select * from shares_owned where share_code = '$this->shareCode'");
            if ($sharesOwned->read()) {
                $shareBuyPrice = $sharesOwned->buy_price * $sharesOwned->quantity / 100;
                $currentValue = $shares->buy_price * $sharesOwned->quantity / 100;
                $profit = currency($currentValue - $shareBuyPrice - ($accountDetails->getTradingFee() * 2));
                if ($profit > 0) {
                    $message = "Share $shares->share_name ($this->shareCode) sold for £$profit profit.";
                    writeLog("Share sold for profit", $message, $this->shareCode, $profit);
                } else {
                    $message = "Share $shares->share_name ($this->shareCode) sold. £$profit loss.";
                    writeLog("STOP LOSS", $message, $this->shareCode, $profit);
                }
                $accountDetails->setTotalProfit($accountDetails->getTotalProfit() + $profit);
                $accountDetails->setAccountBalance($accountDetails->getAccountBalance() + $currentValue - $accountDetails->getTradingFee());
            } else {
                echo "<div class='alert alert-warning'>Share already sold</div>";
            }
        } else {
            $sharesOwned = new sqlDataset("select * from shares_owned where share_code = '$this->shareCode'");
            $sharesOwned->read();
            $shareBuyPrice = $sharesOwned->buy_price * $sharesOwned->quantity / 100;
            $currentValue = 0;
            $profit = currency(0 - $shareBuyPrice - ($accountDetails->getTradingFee()));
            $accountDetails->setTotalProfit($accountDetails->getTotalProfit() + $profit);
            $accountDetails->setAccountBalance($accountDetails->getAccountBalance() + $currentValue - $accountDetails->getTradingFee());
            $message = "Share $this->shareCode no longer traded. £$profit loss.";
            writeLog("NO LONGER TRADED", $message, $this->shareCode, $profit);
        }
        $deleteSharesOwned = new sqlCommand("delete from shares_owned where share_code = '$this->shareCode'");
        if (!$deleteSharesOwned) {
            die('Unable to delete shares owned');
        }
        echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>Share $this->shareCode sold<button type='button' class='close' data-dismiss='alert' aria-label='Close'>
    <span aria-hidden='true'>&times;</span></button></div>";
        return true;
    }
}
