<?php

namespace Michalholubec\Tests\Factories;

use Michalholubec\Factories\AbstractFactory;
use Michalholubec\Tests\Entities\Contact;

/**
 * @extends AbstractFactory<Contact>
 */
class ContactFactory extends AbstractFactory
{
	protected function model(): string
	{
		return Contact::class;
	}

	protected function definition(): array
	{
		return [
			'email' => $this->faker()->email,
		];
	}

	public function withCountry(): ContactFactory
	{
		return $this->set('country', CountryFactory::new());
	}
}