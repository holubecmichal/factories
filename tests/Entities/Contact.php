<?php
namespace Michalholubec\Tests\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="contacts")
 * @ORM\Entity
 */
class Contact
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
	protected $email;

	/**
	 * @ORM\OneToOne(targetEntity="User", inversedBy="contact")
	 * @ORM\JoinColumn(name="user_id")
	 */
	protected $user;

	/**
	 * @ORM\ManyToOne(targetEntity="Country")
	 */
	protected $country;

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getUser(): User
	{
		return $this->user;
	}

	public function getCountry(): Country
	{
		return $this->country;
	}
}
