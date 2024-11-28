<?php

namespace shares;

require "readJsonFile.php";

require_once "myClasses.php";

require_once "classes/shareClass.php";



set_time_limit(0);

$yahooParms = "snd1t1l1verys";

$alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

//
// main code here
//

require "sharesDb.php";

echo "**STARTING SHARE MONITOR**\n";

echo "Guessing two digit shares \n";
for ($b = 0; $b < 36; $b++) {
    for ($c = 0; $c < 36; $c++) {
        $share = substr($alphabet, $b, 1) . substr($alphabet, $c, 1);
        echo "Checking $share\n";
        $shareDetail = new sqlDataset("select * from share_detail where share_code = '$share'");
        if ($shareDetail->rowCount() == 0) {
            echo "New share $share\n";
            $url = "https://query1.finance.yahoo.com/v10/finance/quoteSummary/{$share}.L?modules=price,summaryProfile,quoteType,financialData";
            if ($urlResult = file_get_contents($url)) {
                echo "URl result \n\n $urlResult \n\n";
                readJsonFile($urlResult, $share);
            }
        }
    }
}

echo "Guessing three digit shares \n";

for ($a = 0; $a < 36; $a++) {
    for ($b = 0; $b < 36; $b++) {
        for ($c = 0; $c < 36; $c++) {
            $share = substr($alphabet, $a, 1) . substr($alphabet, $b, 1) . substr($alphabet, $c, 1);
            echo "Checking $share\n";
            $shareDetail = new sqlDataset("select * from share_detail where share_code = '$share'");
            if ($shareDetail->rowCount() == 0) {
                echo "New share $share\n";
                $url = "https://query1.finance.yahoo.com/v10/finance/quoteSummary/{$share}.L?modules=price,summaryProfile,quoteType,financialData";
                if ($urlResult = file_get_contents($url)) {
                    echo "URl result \n\n $urlResult \n\n";
                    readJsonFile($urlResult, $share);
                }
            }
        }
    }
}

echo "Guessing four digit shares\n";

for ($a = 0; $a < 36; $a++) {
    for ($b = 0; $b < 36; $b++) {
        for ($c = 0; $c < 36; $c++) {
            for ($d = 0; $d < 36; $d++) {
                $share = substr($alphabet, $a, 1) . substr($alphabet, $b, 1) . substr($alphabet, $c, 1) . substr($alphabet, $d, 1);
                if ($share >= 'UAAA') {
                echo "Checking $share\n";
                $shareDetail = new sqlDataset("select * from share_detail where share_code = '$share'");
                if ($shareDetail->rowCount() == 0) {
                    echo "New share $share\n";
                    $url = "https://query1.finance.yahoo.com/v10/finance/quoteSummary/{$share}.L?modules=price,summaryProfile,quoteType,financialData";
                    if ($urlResult = @file_get_contents($url)) {
//                        echo "URl result \n\n $urlResult \n\n";
                        readJsonFile($urlResult, $share);
                    }
                }
                }
            }
        }
    }
}

?>
