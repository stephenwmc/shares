<?php

namespace Shares\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Web documents table
 */
class Dividends extends Eloquent
{
    protected $table = "dividends";
    protected $primaryKey = "dividend_uid";
}
