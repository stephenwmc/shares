<?php

namespace Shares\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Web documents table
 */
class SharesOwned extends Eloquent
{
    protected $table = "shares_owned";
    protected $primaryKey = "shares_owned_uid";
}
