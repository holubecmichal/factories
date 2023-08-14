<?php

use Phinx\Migration\AbstractMigration;

class CreateCountriesTable extends AbstractMigration
{
    public function change()
    {
		$this->table('countries')
			->addColumn('title', 'string')
			->create();
    }
}
