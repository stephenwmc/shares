<?php
require_once "myClasses.php";
require_once "sharesDb.php";

class dividendClass
{

    public $dividend;
    public $dividend_date;
    public $yield;
    public $shareCode;
    public $shareName;
    public $divDate;

    function __construct($shareDetail)
    {
        $this->shareCode = $shareDetail->share_code;
        $this->shareName = $shareDetail->share_name;
        $this->yield = number_format(($shareDetail->dividend / $shareDetail->buy_price * 100), 3, ".", "");
        $this->divDate = $shareDetail->dividend_date;
        $updateShareDetail = new sqlCommand("update share_detail set yield = $this->yield where share_code = '$this->shareCode'");
    }

    function display()
    {
        echo "<p>Share $this->shareCode, $this->shareName , yield = $this->yield, div date = $this->divDate</p>";
    }

}

$now = mktime() - (60 * 60 * 24 * 365 * 1);
$twoYear = date('Y-m-d H:i:s', $now);
echo "<p>All dividends paid since $twoYear</p>";
$shareDetail = new sqlDataset("select * from share_detail where yield <> 0 and dividend_date > '$twoYear' order by yield desc limit 15");
while ($shareDetail->read()) {
    $dividends[] = new dividendClass($shareDetail);
}
$newDividend = new newDividendClass();

$monthago = date('Y-m-d H:i:s', mktime() - (60 * 60 * 24 * 31));
$noDiv = new sqlDataset("select * from share_detail where dividend_date_changed < '$monthago' or dividend_date_changed is null");
if ($noDiv->rowCount() == 0) {
    $onLoad = "";
} else {
    $onLoad = "onLoad=\"timeOut=setInterval('window.location.reload()', 25000); \"";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN">
<html>
    <head>
        <link rel=stylesheet href="styles.css" style type="text/css">
        <title>Dividend Maintenance</title>
    </head>
    <body <?php echo $onLoad; ?>>
        <div id='dividends'>
<?php
if (isset($dividends)) {
    for ($f = 0; $f < count($dividends); $f++) {
        $dividends[$f]->display();
    }
}
?>
        </div>
        <div id='newDiv'>
<?php $newDividend->display(); ?>
        </div></body>
</html>