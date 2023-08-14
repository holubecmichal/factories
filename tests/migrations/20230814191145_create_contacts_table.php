<?php

use Phinx\Migration\AbstractMigration;

class CreateContactsTable extends AbstractMigration
{
    public function change()
    {
		$this->table('contacts')
			->addColumn('email', 'string')
			->addColumn('user_id', 'integer')
			->addColumn('country_id', 'integer')
			->addForeignKey(['user_id'], 'users')
			->addForeignKey(['country_id'], 'countries')
			->create();
    }
}
