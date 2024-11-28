<?php

namespace Shares\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Web documents table
 */
class AccountDetail extends Eloquent
{
    protected $table = "account_details";
    protected $primaryKey = "account_details_uid";
}
