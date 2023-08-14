<?php
namespace Michalholubec\Tests\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="countries")
 * @ORM\Entity
 */
class Country
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
	protected $title;

	public function getTitle()
	{
		return $this->title;
	}
}
