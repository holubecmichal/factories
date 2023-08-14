<?php

use Phinx\Migration\AbstractMigration;

class CreateAddressesTable extends AbstractMigration
{
    public function change()
    {
		$this->table('addresses')
			->addColumn('street', 'string')
			->addColumn('postcode', 'string')
			->addColumn('city', 'string')
			->addColumn('user_id', 'integer')
			->addForeignKey(['user_id'], 'users')
			->create();
    }
}
