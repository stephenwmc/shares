<?php

namespace shares;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once "sharesDb.php";
require_once "myClasses.php";
require_once "model/sharesOwned.php";
require_once "globals.php";

function showBadges($badges)
{
    foreach ($badges as $k => $v) {
        $badgeCss = str_replace("btn-", "badge badge-", $k);
        echo " <span class='{$badgeCss}'>$v</span>";
    }
}

$globals->lowestProfit = 0;
$globals->lowestProfitShare = "";
$globals->profitCount = 0;

$myShares = [];
$badges = [];

$shares = new sqlDataset("select o.share_code from shares_owned o inner join share_detail d on d.share_code = o.share_code order by (d.buy_price * o.quantity) desc");
while ($shares->read()) {
    $myShare = new sharesOwned($shares->share_code);
    $myShares[] = $myShare;
    if (isset($badges[$myShare->btnColour])) {
        $badges[$myShare->btnColour] ++;
    } else {
        $badges[$myShare->btnColour] = 1;
    }
}

if ($shares->rowCount() == 0) {
    echo "<h3>No shares currently owned</h3>";
} else {
    ?>
    <h3>Shares Owned<?php showBadges($badges); ?></h3>
    <div class="table-responsive-sm text-nowrap">
        <table class="table table-borderless table-sm">
            <thead class="thead-dark">
                <tr>
                    <th scope="col">Share</th>
                    <th>Description</th>
                    <th class='th-sm'>Sector</th>
                    <th scope="col">Current Value</th>
                    <th scope="col">Profit</th>
                    <th spoce="col">Dividend</th>
                    <th scope="col">Days Owned</th>
                    <th scope="col">Date Changed</th>
                    <th scope="col">Qty</th>
                    <th scope="col">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($myShares as $myShare) {
                    $myShare->display();
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}
