<?php

namespace Shares\Model;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Web documents table
 */
class ShareDetail extends Eloquent
{
    protected $table = "share_detail";
    protected $primaryKey = "share_detail_uid";
}
