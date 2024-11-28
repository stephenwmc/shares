<?php

namespace shares;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once "sharesDb.php";
require_once("myClasses.php");
//require_once "globals.php";

$shareDetail = new sqlDataset("select * from share_detail where date_changed > date_sub(now(), interval 1 hour) order by date_changed desc limit 10");

$tickerString = "<div class='alert alert-info text-nowrap' role='alert' data-spy='marquee'>";
while ($shareDetail->read()) {
    $tickerString .= substr($shareDetail->date_changed,10,9) . " - " .$shareDetail->share_name . " (" . $shareDetail->share_code . ") " . $shareDetail->buy_price . " " . $shareDetail->last_movement . " ... ";
}
$tickerString .= "</div>";
echo $tickerString;
