<?php

namespace Shares\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Web documents table
 */
class Recommendations extends Eloquent
{
    protected $table = "recommendations";
    protected $primaryKey = "recommendation_uid";
}
