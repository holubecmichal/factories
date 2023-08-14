<?php

namespace Michalholubec\Tests\Factories;

use Michalholubec\Factories\AbstractFactory;
use Michalholubec\Tests\Entities\Address;

class AddressFactory extends AbstractFactory
{
	protected function model(): string
	{
		return Address::class;
	}

	protected function definition(): array
	{
		return [
			'street' => $this->faker()->streetAddress,
			'postcode' => $this->faker()->postcode,
			'city' => $this->faker()->city
		];
	}
}