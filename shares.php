<?php

namespace shares;

set_time_limit(0);
error_reporting(E_ALL);

date_default_timezone_set("Europe/London");

require_once "sharesDb.php";
require_once("myClasses.php");
require_once("model/shareDetail.php");
require_once "model/accountDetail.php";

require_once("globals.php");

//
// main code here
//

$today = date('Y-m-d');
//$today = '2010/11/26';

$accountDetails = new accountDetail();
$accountDetails->read();

$globals->rec = "";

//$updateShareDetail = new sqlCommand("update share_detail set grid_position = 0");

$shareDetails = new shareDetail("1 = 1 order by max_profit desc");

$totLn = 0;
?>
<p>Share recommendation <?php echo $globals->rec; ?></p>
<h3>Watched Shares</h3>
<table class='table table-sm'>
    <thead class="thead-dark">
    <tr>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th style="width:50px">Share</th>
        <th style="width:150px">Description</th>
        <th>Sector</th>
        <th>Price</th>
        <th>Volume</th>
        <th>Rec</th>
        <th>Max Value</th>
        <th>Max Profit</th>
        <th>Date</th>
        <th>Movement</th>
        <th style='width:70px'>% between min & max</th>
        <th>Candlestick Now</th>
        <th>C/S 1d</th>
        <th>C/S 2d</th>
        <th>C/S 3d</th>
    </tr>
    </thead>
    <tbody>
    <?php
    while ($shareDetails->read()) {
        $shareDetails->display();
    }
    ?>
    </tbody>
</table>
