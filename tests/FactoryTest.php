<?php

namespace Michalholubec\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Faker\Factory;
use Michalholubec\Factories\AbstractFactory;
use Michalholubec\Tester\TestCase;
use Michalholubec\Tests\Entities\Address;
use Michalholubec\Tests\Entities\Contact;
use Michalholubec\Tests\Entities\Country;
use Michalholubec\Tests\Entities\User;
use Michalholubec\Tests\Factories\AddressFactory;
use Michalholubec\Tests\Factories\ContactFactory;
use Michalholubec\Tests\Factories\UserFactory;
use Tester\Assert;

$container = require __DIR__ . '/bootstrap.php';

/**
 * @testcase
 */
class FactoryTest extends TestCase
{
	/**
	 * @var EntityManagerInterface
	 */
	private $entityManager;

	protected function setUp()
	{
		$this->migrate();

		AbstractFactory::init($this->getByType(ManagerRegistry::class), Factory::create());

		$this->entityManager = $this->getByType(EntityManagerInterface::class);
	}

	public function testPersistUser(): void
	{
		$newUser = UserFactory::new()->makeOne();

		Assert::noError(function () { $this->entityManager->flush(); });

		$users = $this->entityManager->createQueryBuilder()->select('user')->from(User::class, 'user')->getQuery()->execute();

		Assert::count(1, $users);

		$dbUser = $users[0];

		\assert($dbUser instanceof User);

		Assert::equal($dbUser->getFirstName(), $newUser->getFirstName());
		Assert::equal($dbUser->getLastName(), $newUser->getLastName());
	}

	public function testPersistManyUser(): void
	{
		$newUser = UserFactory::new()->count(5)->make();

		Assert::noError(function () { $this->entityManager->flush(); });

		$users = $this->entityManager->createQueryBuilder()->select('user')->from(User::class, 'user')->getQuery()->execute();

		Assert::count(5, $users);
	}


	public function testOneToOneHasFactory(): void
	{
		$user = UserFactory::new()->has(ContactFactory::new())->makeOne();

		Assert::equal($user, $user->getContact()->getUser());

		Assert::noError(function () { $this->entityManager->flush(); });

		$users = $this->entityManager->createQueryBuilder()->select('user')->from(User::class, 'user')->getQuery()->execute();

		Assert::count(1, $users);

		$dbUser = $users[0];

		\assert($dbUser instanceof User);

		Assert::equal($dbUser->getFirstName(), $user->getFirstName());
		Assert::equal($dbUser->getLastName(), $user->getLastName());

		Assert::equal($dbUser->getContact(), $user->getContact());
		Assert::equal($dbUser->getContact()->getEmail(), $user->getContact()->getEmail());
	}

	public function testOneToOneHasFactoryInstance(): void
	{
		$contact = ContactFactory::new()->makeOne();

		$user = UserFactory::new()->has($contact)->makeOne();

		Assert::equal($user, $user->getContact()->getUser());

		Assert::noError(function () { $this->entityManager->flush(); });

		$users = $this->entityManager->createQueryBuilder()->select('user')->from(User::class, 'user')->getQuery()->execute();

		Assert::count(1, $users);

		$dbUser = $users[0];

		\assert($dbUser instanceof User);

		Assert::equal($dbUser->getFirstName(), $user->getFirstName());
		Assert::equal($dbUser->getLastName(), $user->getLastName());

		Assert::equal($dbUser->getContact(), $user->getContact());
		Assert::equal($dbUser->getContact()->getEmail(), $user->getContact()->getEmail());
	}

	public function testOneToOneHasEntityInstance(): void
	{
		ContactFactory::new()->makeOne();

		Assert::noError(function () { $this->entityManager->flush(); });

		$dbContact = $this->entityManager->createQueryBuilder()->select('contact')->from(Contact::class, 'contact')->getQuery()->getOneOrNullResult();

		Assert::type(Contact::class, $dbContact);

		$user = UserFactory::new()->has($dbContact)->makeOne();

		Assert::equal($user, $user->getContact()->getUser());

		Assert::noError(function () { $this->entityManager->flush(); });

		$users = $this->entityManager->createQueryBuilder()->select('user')->from(User::class, 'user')->getQuery()->execute();

		Assert::count(1, $users);

		$dbUser = $users[0];

		\assert($dbUser instanceof User);

		Assert::equal($dbUser->getFirstName(), $user->getFirstName());
		Assert::equal($dbUser->getLastName(), $user->getLastName());

		Assert::equal($dbUser->getContact(), $user->getContact());
		Assert::equal($dbUser->getContact()->getEmail(), $user->getContact()->getEmail());
	}

	public function testOneToOneForFactory(): void
	{
		$contact = ContactFactory::new()->for(UserFactory::new())->makeOne();

		Assert::equal($contact, $contact->getUser()->getContact());

		Assert::noError(function () { $this->entityManager->flush(); });

		$contacts = $this->entityManager->createQueryBuilder()->select('contact')->from(Contact::class, 'contact')->getQuery()->execute();

		Assert::count(1, $contacts);

		$dbContact = $contacts[0];

		\assert($dbContact instanceof Contact);

		Assert::equal($dbContact->getEmail(), $contact->getEmail());
		Assert::equal($dbContact->getUser(), $contact->getUser());
	}

	public function testOneToOneForFactoryInstance(): void
	{
		$user = UserFactory::new()->makeOne();

		$contact = ContactFactory::new()->for($user)->makeOne();

		Assert::equal($user, $contact->getUser());

		Assert::noError(function () { $this->entityManager->flush(); });

		$contacts = $this->entityManager->createQueryBuilder()->select('contact')->from(Contact::class, 'contact')->getQuery()->execute();

		Assert::count(1, $contacts);

		$dbContact = $contacts[0];

		\assert($dbContact instanceof Contact);

		Assert::equal($dbContact->getEmail(), $contact->getEmail());
		Assert::equal($dbContact->getUser(), $contact->getUser());
	}

	public function testOneToOneForEntityInstance(): void
	{
		UserFactory::new()->makeOne();

		Assert::noError(function () { $this->entityManager->flush(); });

		$dbUser = $this->entityManager->createQueryBuilder()->select('user')->from(User::class, 'user')->getQuery()->getOneOrNullResult();

		$contact = ContactFactory::new()->for($dbUser)->makeOne();

		Assert::equal($dbUser, $contact->getUser());

		Assert::noError(function () { $this->entityManager->flush(); });

		$contacts = $this->entityManager->createQueryBuilder()->select('contact')->from(Contact::class, 'contact')->getQuery()->execute();

		Assert::count(1, $contacts);

		$dbContact = $contacts[0];

		\assert($dbContact instanceof Contact);

		Assert::equal($dbContact->getEmail(), $contact->getEmail());
		Assert::equal($dbContact->getUser(), $contact->getUser());
	}

	public function testOneToManyFactory(): void
	{
		$user = UserFactory::new()->has(AddressFactory::new()->count(5))->makeOne();

		Assert::count(5, $user->getAddresses());

		foreach ($user->getAddresses() as $address) {
			Assert::equal($address->getUser(), $user);
		}

		Assert::noError(function () { $this->entityManager->flush(); });

		Assert::count(5, $this->entityManager->createQueryBuilder()->select('address')->from(Address::class, 'address')->getQuery()->execute());
	}

	public function testOneToManyFactoryCollection(): void
	{
		$addresses = AddressFactory::new()->count(5)->make();

		$user = UserFactory::new()->has($addresses)->makeOne();

		Assert::count(5, $user->getAddresses());

		foreach ($user->getAddresses() as $address) {
			Assert::equal($address->getUser(), $user);
		}

		Assert::noError(function () { $this->entityManager->flush(); });

		Assert::count(5, $this->entityManager->createQueryBuilder()->select('address')->from(Address::class, 'address')->getQuery()->execute());
	}

	public function testOneToManyRelationship(): void
	{
		$user = UserFactory::new()->has(AddressFactory::new()->count(5), 'addresses')->makeOne();

		Assert::count(5, $user->getAddresses());

		foreach ($user->getAddresses() as $address) {
			Assert::equal($address->getUser(), $user);
		}

		Assert::noError(function () { $this->entityManager->flush(); });

		Assert::count(5, $this->entityManager->createQueryBuilder()->select('address')->from(Address::class, 'address')->getQuery()->execute());
	}

	public function testManyToOneFactory(): void
	{
		$addresses = AddressFactory::new()
			->for(UserFactory::new())
			->count(5)
			->make();

		Assert::count(5, $addresses);

		Assert::noError(function () { $this->entityManager->flush(); });

		Assert::count(5, $this->entityManager->createQueryBuilder()->select('address')->from(Address::class, 'address')->getQuery()->execute());
	}

	public function testManyToOneFactoryInstance(): void
	{
		$user = UserFactory::new()->makeOne();

		$addresses = AddressFactory::new()
			->for($user)
			->count(5)
			->make();

		Assert::count(5, $addresses);

		Assert::same($user->getAddresses()->toArray(), $addresses->toArray());

		foreach ($addresses as $address) {
			Assert::equal($address->getUser(), $user);
		}

		Assert::noError(function () { $this->entityManager->flush(); });

		Assert::count(5, $this->entityManager->createQueryBuilder()->select('address')->from(Address::class, 'address')->getQuery()->execute());
	}

	public function testManyToOneEntityInstance(): void
	{
		$user = UserFactory::new()->makeOne();

		Assert::noError(function () { $this->entityManager->flush(); });

		$dbUser = $this->entityManager->createQueryBuilder()->select('user')->from(User::class, 'user')->getQuery()->getOneOrNullResult();

		$addresses = AddressFactory::new()
			->for($dbUser)
			->count(5)
			->make();

		Assert::count(5, $addresses);

		Assert::same($user->getAddresses()->toArray(), $addresses->toArray());

		foreach ($addresses as $address) {
			Assert::equal($address->getUser(), $user);
		}

		Assert::noError(function () { $this->entityManager->flush(); });

		Assert::count(5, $this->entityManager->createQueryBuilder()->select('address')->from(Address::class, 'address')->getQuery()->execute());
	}

	public function testManyToOneRelationship(): void
	{
		$addresses = AddressFactory::new()
			->for(UserFactory::new(), 'user')
			->count(5)
			->make();

		Assert::count(5, $addresses);

		Assert::noError(function () { $this->entityManager->flush(); });

		Assert::count(5, $this->entityManager->createQueryBuilder()->select('address')->from(Address::class, 'address')->getQuery()->execute());
	}

	public function testSet(): void
	{
		$user = UserFactory::new()
			->firstName('tester')
			->lastName('factory')
			->makeOne();

		Assert::noError(function () { $this->entityManager->flush(); });

		Assert::equal('tester', $user->getFirstName());
		Assert::equal('factory', $user->getLastName());

		$dbUser = $this->entityManager->createQueryBuilder()->select('user')->from(User::class, 'user')->getQuery()->getOneOrNullResult();

		Assert::equal($user->getFirstName(), $dbUser->getFirstName());
		Assert::equal($user->getLastName(), $dbUser->getLastName());
	}

	public function testChain(): void
	{
		UserFactory::new()->withContact()->makeOne();

		Assert::noError(function () { $this->entityManager->flush(); });

		$dbUser = $this->entityManager->createQueryBuilder()->select('user')->from(User::class, 'user')->getQuery()->getOneOrNullResult();

		Assert::type(User::class, $dbUser);
		Assert::type(Contact::class, $dbUser->getContact());
		Assert::type(Country::class, $dbUser->getContact()->getCountry());
	}

	protected function getMigrationPaths(): array
	{
		return [__DIR__ . '/migrations'];
	}

	protected function getSeedPath(): string
	{
		return '';
	}
}

(new FactoryTest($container))->run();