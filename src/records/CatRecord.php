<?php

namespace craftsnippets\craftcatsmanager\records;

use Craft;
use craft\db\ActiveRecord;

use craftsnippets\craftcatsmanager\helpers\DbTables;

class CatRecord extends ActiveRecord
{

    public static function tableName()
    {
        return DbTables::CATS;
    }
}
