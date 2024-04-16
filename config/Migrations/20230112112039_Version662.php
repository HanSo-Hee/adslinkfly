<?php

use Migrations\AbstractMigration;

class Version662 extends AbstractMigration
{
    public $autoId = false;

    public function up()
    {
        $this->execute("SET SESSION sql_mode = ''");

        $this->execute("UPDATE `links` SET `last_activity` = UTC_TIMESTAMP() WHERE `last_activity` IS NULL;");
        $this->execute("ALTER TABLE `links` CHANGE `last_activity` `last_activity` DATETIME NULL DEFAULT CURRENT_TIMESTAMP;");
    }
}
