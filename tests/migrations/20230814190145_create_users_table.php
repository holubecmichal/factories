<?php

use Phinx\Migration\AbstractMigration;

class CreateUsersTable extends AbstractMigration
{
    public function change()
    {
    	$this->table('users')
		    ->addColumn('first_name', 'string')
		    ->addColumn('last_name', 'string')
		    ->create();
    }
}
