<?php

namespace shares;

require_once "abstractModel.php";
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class accountDetail extends abstractModel
{
    public $tableName = "account_details";

    public function daysRunning()
    {
        $dt1 = new \DateTime($this->date_started);
        $dt2 = new \DateTime();
        return date_diff($dt1, $dt2)->format('%a');
        //        , datediff(curdate(), date_started) as days_running
    }

    public function getShareVal()
    {
        if ($this->account_balance > (250 + $this->trading_fee)) {
            return 200;
        } elseif ($this->account_balance > (150 + $this->trading_fee)) {
            return 100;
        } elseif ($this->account_balance > (50 + $this->trading_fee)) {
            return 20;
        } elseif ($this->account_balance > (20 + $this->trading_fee)) {
            return 10;
        } elseif ($this->account_balance > (10 + $this->trading_fee)) {
            return 5;
        } elseif ($this->account_balance > (3 + $this->trading_fee)) {
            return 2;
        } else {
            return 100;
        }
    }

    public function getTradingFee()
    {
        return $this->trading_fee;
    }

    public function getAccountBalance()
    {
        return number_format($this->account_balance, 2, '.', '');
    }

    public function setAccountBalance($val)
    {
        $updateAccountDetails = new sqlCommand("update account_details set account_balance = " . $val);
    }

    public function getSellProfit()
    {
        return 20;
    }

    public function getTotalProfit()
    {
        return $this->total_profit;
    }

    public function setTotalProfit($val)
    {
        $updateAccountDetails = new sqlCommand("update account_details set total_profit = " . $val);
    }

    public function getTotalDividendIncome()
    {
        $dividendIncome = new sqlDataset("select sum(dividend_amount) as dividend_income from dividends");
        if ($dividendIncome->read()) {
            return $dividendIncome->dividend_income;
        }
        return 0;
    }

    public function getPendingDividends()
    {
        $sql = new sqlDataset("select sum(floor(forward_eps * quantity)) as pending from share_detail sd inner join shares_owned so on so.share_code = sd.share_code 
where ex_dividend_date > coalesce((select (dividend_date) from dividends d where d.share_code = sd.share_code 
order by dividend_date desc limit 1),'1970-01-01')");
        if ($sql->read()) {
            return $sql->pending / 100;
        }
        return 0;
    }
}
