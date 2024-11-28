<?php

namespace shares;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class abstractModel
{

    function __construct($where = "")
    {
        global $link;

        if (!isset($this->sql)) {
            $this->sql = "select * from {$this->tableName}";
        }
        if ($where != "") {
            $this->sql .= " where $where";
        }
        $this->result = mysqli_query($link, $this->sql);
        if (!$this->result) {
            die('Unable to open table : ' . $this->sql . ' : ' . mysqli_error($link));
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
