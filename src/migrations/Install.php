<?php

namespace craftsnippets\craftcatsmanager\migrations;

use Craft;
use craft\db\Migration;
use craftsnippets\craftcatsmanager\db\Table;

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
        $this->archiveTableIfExists(Table::CATS);
        $this->createTable(
            Table::CATS,
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

    protected function removeTables()
    {
        $this->dropTableIfExists(Table::CATS);
    }
}
