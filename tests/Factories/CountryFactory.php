<?php

namespace Michalholubec\Tests\Factories;

use Michalholubec\Factories\AbstractFactory;
use Michalholubec\Tests\Entities\Country;

class CountryFactory extends AbstractFactory
{
	protected function model(): string
	{
		return Country::class;
	}

	protected function definition(): array
	{
		return [
			'title' => $this->faker()->country
		];
	}
}