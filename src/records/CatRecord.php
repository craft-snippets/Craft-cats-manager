<?php

namespace craftsnippets\craftcatsmanager\records;

use Craft;
use craft\db\ActiveRecord;

use craftsnippets\craftcatsmanager\db\Table;

class CatRecord extends ActiveRecord
{

    public static function tableName()
    {
        return Table::CATS;
    }
}
