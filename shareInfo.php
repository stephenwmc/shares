<?php

namespace shares;

require "model/accountDetail.php";
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
error_reporting(E_ALL);

require_once "sharesDb.php";
require_once "myClasses.php";

$accountDetails = new accountDetail();

if ($accountDetails->read()) {
    $totalCurrentValue = 0;
    $totalCurrentProfit = 0;
    $shares = new sqlDataset("select shares_owned.share_code, share_detail.share_name, shares_owned.buy_price as orig_price, share_detail.sell_price as current_price, share_detail.grid_position, shares_owned.quantity, shares_owned.date_bought, share_detail.date_changed from shares_owned inner join share_detail on shares_owned.share_code = share_detail.share_code");
    while ($shares->read()) {
        $shareBuyPrice = $shares->orig_price * $shares->quantity / 100;
        $currentValue = $shares->current_price * $shares->quantity / 100;
        $currentProfit = $currentValue - $shareBuyPrice;
        $totalCurrentValue += $shares->current_price * $shares->quantity / 100;
        $totalCurrentProfit += $currentProfit;
    }
} else {
    die("Unable to read settings");
}

$totalProfit = $totalCurrentValue - $accountDetails->amount_invested;

$shareDetail = new sqlDataset("select share_code,share_name,date_added from share_detail order by date_added desc limit 3");


$shareActivity = new sqlDataset("select * from share_activity order by activity_date desc limit 50");
?>
<h3>Information</h3>
<div class="table-responsive-sm text-nowrap">
    <table class='table table-sm table-borderless'>
        <tr>
            <td scope="col">Balance : </th>
            <td align='right'><?= currency($accountDetails->account_balance); ?></td>
        </tr>
        <tr>
            <td>Amount Invested</td>
            <td align="right"><?= $accountDetails->amount_invested ?></td>
        </tr>
        <tr>
            <td>Current Share Value</td>
            <td align='right'><?= currency($totalCurrentValue); ?></td>
        </tr>
        <tr>
            <td>Total Dividend Income </td>
            <td align='right'><?= currency($accountDetails->getTotalDividendIncome()); ?></td>
        </tr>
        <tr>
            <td>Pending Dividends</td>
            <td align='right'><?= currency($accountDetails->getPendingDividends()); ?></td>
        </tr>
        <tr>
            <td>Current Share Profit</td>
            <td align='right'><?= currency($totalCurrentProfit); ?></td>
        </tr>
        <tr>
            <td>Total Profit</td>
            <td align='right'><?= currency($totalProfit); ?></td>
        </tr>
        <tr>
            <td>Return Rate P.A. %</td>
            <td align="right"><?= currency($totalProfit / $accountDetails->amount_invested * 100 / ($accountDetails->daysRunning() / 365.25)) ?></td>
        </tr>
        <tr>
            <td>Last checked at </td>
            <td align='right'><?= date('H:i:s'); ?></td>
        </tr>
        <tr>
            <td>Days running</td>
            <td align="right"><?= $accountDetails->daysRunning(); ?></td>
        </tr>
        <tr>
            <td>Profit per day</td>
            <td align="right"><?= currency($totalCurrentProfit / $accountDetails->daysRunning()); ?></td>
        </tr>
        <tr>
            <td colspan="2"><?= $accountDetails->update_status; ?></td>
        </tr>
    </table>
</div>

<h3>New Shares</h3>
<div class="table-responsive-sm">
    <table class="table table-sm table-borderless">
        <tbody>
        <?php
        while ($shareDetail->read()) {
            echo "<tr><td>".$shareDetail->share_code."</td><td>".$shareDetail->share_name."</td><td>".displayDate($shareDetail->date_added)."</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
<h3>Dividends</h3>
<table class="table table-sm table-borderless">
<?php

$dividends = new sqlDataset("select d.share_code,share_name,dividend_amount,dividend_date from dividends d inner join share_detail
s on d.share_code = s.share_code where dividend_date > now() - INTERVAL 12 month order by dividend_date desc");


while ($dividends->read()) {
    echo "<tr><td>$dividends->share_name ($dividends->share_code)</td><td>".currency($dividends->dividend_amount)."</td><td>" . displayDate($dividends->dividend_date) . "</td></tr>";
}
?>
</table>
</div>
