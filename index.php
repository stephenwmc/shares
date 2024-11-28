<?php

namespace shares;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require "vendor/autoload.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN">
<html>
    <head>
        <meta charset="UTF-8" />
        <link rel="stylesheet" href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <script type="text/javascript" src="js/jquery.js"></script>
        <script src="vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <script type="text/javascript">
            function loadAllPanes() {
                loadPanes();
                loadInfoPane();
            }
            function loadInfoPane() {
                jQuery('#infoDiv').load('shareInfo.php');
                jQuery('#tickerDiv').load('shareTicker.php');
            }
            var loadInfo = setInterval(
                    function () {
                        loadInfoPane();
                    }, 5000);
            function loadPanes() {
                jQuery('#sharesOwnedDiv').load("sharesOwned.php");
                jQuery('#sharesDiv').load("shares.php");
            }
            var loadAll = setInterval(
                    function () {
                        loadPanes();
                    }, 30000);

            function sellShare(shr) {
                jQuery('#shareActionDiv').load("sellShare.php?shr=" + shr);
                jQuery('#sharesOwnedDiv').load("sharesOwned.php");
            }
            function buyShare(shr) {
                jQuery('#shareActionDiv').load("buyShare.php?shr=" + shr);
                jQuery('#sharesOwnedDiv').load("sharesOwned.php");
            }
        </script>
        <title>Share Monitor</title>
    </head>
    <body onload="loadPanes()">
        <div id="mainbody">
            <div class='page-header bg-dark text-light p-3'>
                <h1 class="display-4">Share Monitor</h1>
            </div>
            <div id="tickerDiv">Ticker here</div>
            <div id="shareActionDiv">
                <div class='alert alert-success alert-dismissible fade show' role='alert'>Alerts will appear here
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                        <span aria-hidden='true'>&times;</span>
                    </button>
                </div>
            </div>
            <div class='container-fluid'>
                <div class="row">
                    <div class='col-sm col-md-auto bg-color-blue' id="sharesOwnedDiv">Loading</div>
                    <div class='col-sm bg-color-yellow' id='infoDiv'>Loading</div>
                    <div class='col-sm bg-light' id="sharesDiv">Loading</div> 
                </div>
            </div>
        </div>
    </body>
</html>