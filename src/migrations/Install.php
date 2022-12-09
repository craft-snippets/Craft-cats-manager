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
        $this->createTables();
        return true;
    }

    public function safeDown()
    {
        $this->removeTables();
        $this->dropProjectConfig();
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

    public function dropProjectConfig(): void
    {
        Craft::$app->projectConfig->remove(Table::CATS_PROJECT_CONFIG);
    }

}
