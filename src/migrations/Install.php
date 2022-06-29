<?php

namespace craftsnippets\craftcatsmanager\migrations;

use Craft;
use craft\db\Migration;
use craftsnippets\craftcatsmanager\helpers\DbTables;

class Install extends Migration
{

    public $driver;

    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            Craft::$app->db->schema->refresh();
        }
        return true;
    }

    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();
        return true;
    }

    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema(DbTables::CATS);
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                DbTables::CATS,
                [
                    'id' => $this->primaryKey(),
                    'uid' => $this->uid(),
                    'order' => $this->integer()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'name' => $this->string()->notNull(),
                    'jsonSettings' => $this->text(),
                ]
            );
        }    

        return $tablesCreated;
    }

    protected function removeTables()
    {
        $this->dropTableIfExists(DbTables::CATS);
    }
}
