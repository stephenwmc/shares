<?php

namespace shares;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once "sharesDb.php";
require_once("myClasses.php");

$dividends = new sqlDataset("select d.share_code,share_name,dividend_amount,dividend_date from dividends d inner join share_detail
s on d.share_code = s.share_code order by dividend_date desc limit 5");

echo "<h2>Dividends</h2>";
while ($dividends->read()) {
    echo "$dividends->share_name ($dividends->share_code) paid $dividends->dividend_amount on " . $dividends->activity_date . "<br/>";
}

?>
