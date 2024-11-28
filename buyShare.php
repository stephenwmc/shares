<?php

namespace shares;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once "sharesDb.php";
    require_once("myClasses.php");
require_once "model/sharesOwned.php";
require_once "globals.php";

$share = filter_input(INPUT_GET, 'shr', FILTER_SANITIZE_ENCODED);

$myShares = new shareDetail($share);
if ($myShares->buyShare()) {
    return true;
} else {
    echo "Unable to buy $share";
}

