<?php

namespace shares;

class sqlDataset
{

    public $sql;
    private $result;

    function __construct($inSql)
    {

        global $link;

        $this->result = mysqli_query($link, $inSql);
        $this->sql = $inSql;
        if (!$this->result) {
            die('Unable to open table : ' . $inSql . ' : ' . mysqli_error($link));
        } else {
            return true;
        }
    }

    function read()
    {

        $resultline = mysqli_fetch_assoc($this->result);
        if ($resultline) {
            foreach ($resultline as $field => $val) {
                $this->$field = $val;
            }
            return true;
        } else {
            return false;
        }
    }

    function rowCount()
    {

        return (mysqli_num_rows($this->result));
    }

}

class sqlCommand
{

    private $result;
    public $sql;

    function __construct($inSql)
    {

        global $link;

        $this->sql = $inSql;
        $this->result = mysqli_query($link, $inSql);
        if (!$this->result) {
            die('Unable to execute command : ' . $inSql . ' : ' . mysqli_error($link));
        } else {
            return true;
        }
    }

}

$db_user = 'shares';
$db_pass = 'flarble';
$db_host = 'localhost';
$db_name = 'shares';

$link = mysqli_connect($db_host, $db_user, $db_pass)
  or die('Could not connect: ' . mysqli_connect_error() . ' ' . date('D-m-Y H:i:s'));
mysqli_select_db($link, $db_name) or die('Could not select database');

date_default_timezone_set('Europe/London');
?>