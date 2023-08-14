<?php
namespace Michalholubec\Tests\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="addresses")
 * @ORM\Entity
 */
class Address
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
	protected $street;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $postcode;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $city;

	/**
	 * @ORM\ManyToOne(targetEntity="User", inversedBy="addresses")
	 * @ORM\JoinColumn(name="user_id")
	 */
	protected $user;

	public function getStreet(): string
	{
		return $this->street;
	}

	public function getPostcode(): string
	{
		return $this->postcode;
	}

	public function getCity(): string
	{
		return $this->city;
	}

	public function getUser(): User
	{
		return $this->user;
	}
}
