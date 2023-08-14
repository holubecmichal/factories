<?php
namespace Michalholubec\Tests\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 */
	private $id;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $firstName;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $lastName;

	/**
	 * @ORM\OneToOne(targetEntity="Contact", mappedBy="user")
	 */
	protected $contact;

	/**
	 * @ORM\OneToMany(targetEntity="Address", mappedBy="user")
	 */
	protected $addresses;

	public function __construct()
	{
		$this->addresses = new ArrayCollection();
	}

	public function getFirstName(): string
	{
		return $this->firstName;
	}

	public function getLastName(): string
	{
		return $this->lastName;
	}

	public function getContact(): Contact
	{
		return $this->contact;
	}

	/**
	 * @return Collection<array-key, Address>
	 */
	public function getAddresses(): Collection
	{
		return $this->addresses;
	}
}
