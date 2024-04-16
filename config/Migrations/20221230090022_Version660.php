<?php

use Migrations\AbstractMigration;

class Version660 extends AbstractMigration
{
    public $autoId = false;

    public function up()
    {
        $this->execute("SET SESSION sql_mode = ''");

        $this->execute("UPDATE `links` SET `last_activity` = UTC_TIMESTAMP() WHERE `last_activity` IS NULL;");

        $rows = [
            [
                'name' => 'external_integration_type',
                'value' => 'none',
            ],
            [
                'name' => 'pressfly_access_url',
                'value' => '',
            ],
            [
                'name' => 'pressfly_secret_key',
                'value' => '',
            ],
            [
                'name' => 'wordpress_access_url',
                'value' => '',
            ],
            [
                'name' => 'wordpress_secret_key',
                'value' => '',
            ],
        ];

        $this->table('options')
            ->insert($rows)
            ->saveData();
    }
}
