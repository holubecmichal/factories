<?php

namespace Michalholubec\Tests\Factories;

use Michalholubec\Factories\AbstractFactory;
use Michalholubec\Tests\Entities\User;

/**
 * @extends AbstractFactory<User>
 */
class UserFactory extends AbstractFactory
{
	protected function model(): string
	{
		return User::class;
	}

	protected function definition(): array
	{
		return [
			'firstName' => $this->faker()->firstName,
			'lastName' => $this->faker()->lastName,
		];
	}

	public function firstName(string $firstName): UserFactory
	{
		return $this->set('firstName', $firstName);
	}

	public function lastName(string $lastName): UserFactory
	{
		return $this->set('lastName', $lastName);
	}

	public function withContact(): UserFactory
	{
		return $this->set('contact', ContactFactory::new()->withCountry());
	}
}