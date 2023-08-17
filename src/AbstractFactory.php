<?php

namespace Michalholubec\Factories;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Generator as Faker;
use Illuminate\Support\Collection;
use ReflectionClass;

/**
 * @template T of object
 */
abstract class AbstractFactory
{
	/**
	 * @var ManagerRegistry
	 */
	static private $managerRegistry;

	/**
	 * @var Faker
	 */
	static private $faker;

	/**
	 * @var string
	 */
	private $class;

	/**
	 * @var int
	 */
	private $amount = 1;

	/**
	 * @var Collection
	 */
	private  $has;

	/**
	 * @var Collection
	 */
	private $for;

	/**
	 * @var Collection
	 */
	private $set;

	/**
	 * @var bool
	 */
	private $persist = true;

	protected function __construct()
	{
		if (self::$managerRegistry === null) {
			throw new \Exception('Before use factory you must call static init method.');
		}

		$this->class = $this->model();

		$this->has = collect();
		$this->for = collect();
		$this->set = collect();
	}

	public static function init(ManagerRegistry $managerRegistry, Faker $faker): void
	{
		self::$managerRegistry = $managerRegistry;
		self::$faker = $faker;
	}

	/**
	 * @return static
	 */
	public static function new(): AbstractFactory
	{
		return new static();
	}

	/**
	 * @return class-string<T>
	 */
	abstract protected function model(): string;

	/**
	 * @return array<mixed>
	 */
	abstract protected function definition(): array;

	/**
	 * Create one model.
	 *
	 * @param array $attributes
	 * @return T
	 */
	public function makeOne(array $attributes = [])
	{
		$this->amount = 1;

		$instance = $this->make($attributes)->first();

		assert($instance instanceof $this->class);

		return $instance;
	}

	/**
	 * Create a collection of models.
	 *
	 * @param array $attributes
	 *
	 * @return Collection<array-key, T>
	 */
	public function make(array $attributes = []): Collection
	{
		$results = [];

		for ($i = 0; $i < $this->amount; $i++) {
			$results[] = $this->makeInstance($attributes);
		}

		$resultsCollection = new Collection($results);

		$this->callAfterMaking($resultsCollection);

		return $resultsCollection;
	}



	/**
	 * @return static
	 */
	public function has($factory, string $relationship = null): AbstractFactory
	{
		$this->has->push([$factory, $relationship]);

		return $this;
	}

	/**
	 * @return static
	 */
	public function for($factory, string $relationship = null): self
	{
		if (($factory instanceof self) && $factory->amount > 1) {
			throw new \Exception('Only one instance can be used for "for" method.');
		}

		$this->for->push([$factory, $relationship]);

		return $this;
	}

	public function count(int $count): AbstractFactory
	{
		$this->amount = $count;

		return $this;
	}

	protected function getManagerRegistry(): ManagerRegistry
	{
		return self::$managerRegistry;
	}

	/**
	 * @param null|object $class
	 * @param object $targetEntity
	 */
	protected function getMappingByClass($targetEntity, $class = null)
	{
		$class = $class !== null ? get_class($class) : $this->class;

		$associations = $this->getAssociations($class);
		
		$mapping = $associations->where('targetEntity', get_class($targetEntity));

		if ($mapping->count() > 1) {
			throw new \Exception(
				sprintf(
					"For %s exists multiple associations [%s]",
					$class,
					$mapping->keys()->implode(', ')
				)
			);
		}

		if ($mapping->count() === 0) {
			throw new \Exception(
				sprintf("For %s not exists association with [%s]", $class, get_class($targetEntity))
			);
		}

		return $mapping->first();
	}

	/**
	 * @param object $entity
	 */
	protected function getMappingByRelationship(string $relationship, $entity)
	{
		$associations = $this->getAssociations($this->class);

		if (!isset($associations[$relationship])) {
			throw new \Exception(sprintf("For %s not exists association with [%s]", get_class($this->class), get_class($entity)));
		}

		return $associations[$relationship];
	}


	protected function faker(): Faker
	{
		return self::$faker;
	}

	/**
	 * Make an instance of the model with the given attributes.
	 *
	 * @param array $attributes
	 *
	 * @return mixed
	 */
	protected function makeInstance(array $attributes = [])
	{
		$definition = $this->definition();

		$set = $this->set->map(function ($value) {
			if ($value instanceof AbstractFactory) {
				return $value->makeOne();
			}

			return $value;
		});

		$definition = array_merge($definition, $set->all());

		$metadata = $this->getClassMetadata($this->class);

		$toManyRelations = (new Collection($metadata->getAssociationMappings()))
			->keys()
			->filter(function ($association) use ($metadata) {
				return $metadata->isCollectionValuedAssociation($association);
			})
			->mapWithKeys(function ($association) {
				return [$association => new ArrayCollection];
			});

		return $this->hydrate(
			$this->class,
			array_merge($toManyRelations->all(), $definition, $attributes)
		);
	}

	protected function callAfterMaking(Collection $instances): void
	{
		$instances->each(function ($instance): void {
			$this->processAssociations($instance);

			if ($this->persist) {
				$this->getManagerForClass($this->class)->persist($instance);
			}
		});
	}

	/**
	 * @param object $instance
	 */
	protected function processAssociations($instance): void
	{
		if ($this->for->isEmpty() && $this->has->isEmpty()) {
			return;
		}

		foreach ($this->for as [$factory, $relationship]) {
			$entity = $this->processFactory($factory)->first();

			if ($relationship !== null) {
				$mapping = $this->getMappingByRelationship($relationship, $entity);
			} else {
				$mapping = $this->getMappingByClass($entity);
			}

			$this->setField($instance, $mapping['fieldName'], $entity);

			if ($mapping['inversedBy'] !== null) {
				if ($mapping['type'] === ClassMetadataInfo::ONE_TO_ONE) {
					$this->setField($entity, $mapping['inversedBy'], $instance);
				} else {
					$this->addToCollection($entity, $mapping['inversedBy'], $instance);
				}
			}
		}

		foreach ($this->has as [$factory, $relationship]) {
			$entities = $this->processFactory($factory);

			if ($relationship !== null) {
				$mapping = $this->getMappingByRelationship($relationship, $entities->first());
			} else {
				$mapping = $this->getMappingByClass($entities->first());
			}

			if ($mapping['type'] === ClassMetadataInfo::ONE_TO_MANY || $mapping['type'] === ClassMetadataInfo::MANY_TO_MANY) {
				$this->setCollection($entities, $instance, $mapping['fieldName']);

				if ($mapping['type'] === ClassMetadataInfo::MANY_TO_MANY) {
					$entityMapping = $this->getMappingByClass($instance, $entities->first());

					foreach ($entities as $entity) {
						$this->addToCollection($entity, $entityMapping['fieldName'], $instance);
					}
				}
			} else {
				$this->setField($instance, $mapping['fieldName'], $entities->first());
			}

			if ($mapping['mappedBy'] !== null) {
				foreach ($entities as $entity) {
					$this->setField($entity, $mapping['mappedBy'], $instance);
				}
			}
		}
	}

	protected function getAssociations(string $class): Collection
	{
		$metadata = $this->getClassMetadata($class);

		assert(property_exists($metadata, 'associationMappings') && is_array($metadata->associationMappings));

		return collect($metadata->associationMappings);
	}

	protected function getClassMetadata(string $class): ClassMetadataInfo
	{
		return $this->getManagerForClass($class)->getClassMetadata($class);
	}

	protected function getManagerForClass(string $class): ObjectManager
	{
		return $this->getManagerRegistry()->getManagerForClass($class);
	}

	/**
	 * @param mixed $factory
	 * @return Collection<int, object>
	 */
	protected function processFactory($factory): Collection
	{
		if ($factory instanceof AbstractFactory) {
			$model = $factory->setPersist($this->persist)->make();
		} else if ($factory instanceof Collection) {
			$model = $factory;
		} else {
			$model = $factory;
		}

		if (!$model instanceof Collection) {
			$items = collect([$model]);
		} else {
			$items = $model;
		}

		return $items;
	}

	/**
	 * @param object $instance
	 * @return array<mixed>
	 */
	protected function getAssociation($instance, string $relationship): array
	{
		$metadata = $this->getClassMetadata(get_class($instance));

		assert(property_exists($metadata, 'associationMappings') && is_array($metadata->associationMappings));

		return $metadata->associationMappings[$relationship];
	}

	/**
	 * @param object $targetEntity
	 * @param mixed $value
	 */
	protected function setField($targetEntity, string $field, $value): void
	{
		$this->setValue(new ReflectionClass($targetEntity), $targetEntity, $field, $value);
	}

	/**
	 * @param object $instance
	 * @param ReflectionClass<T> $reflection
	 * @param mixed $value
	 */
	protected function setValue(ReflectionClass $reflection, $instance, string $field, $value): void
	{
		if ($reflection->hasProperty($field)) {
			$property = $reflection->getProperty($field);
			$property->setAccessible(true);
			$property->setValue($instance, $value);
		} elseif ($parent = $reflection->getParentClass()) {
			$this->setValue($parent, $instance, $field, $value);
		}
	}

	/**
	 * @param object $targetEntity
	 * @param object $toAdd
	 */
	protected function addToCollection($targetEntity, string $field, $toAdd): void
	{
		$collection = $this->getValue(new ReflectionClass($targetEntity), $targetEntity, $field);

		assert($collection instanceof DoctrineCollection);

		$collection = new ArrayCollection($collection->toArray());
		$collection->add($toAdd);

		$this->setField($targetEntity, $field, $collection);
	}

	/**
	 * @param object $instance
	 * @param ReflectionClass<T> $reflection
	 * @return mixed
	 */
	protected function getValue(ReflectionClass $reflection, $instance, string $field)
	{
		if ($reflection->hasProperty($field)) {
			$property = $reflection->getProperty($field);
			$property->setAccessible(true);
			return $property->getValue($instance);
		}

		$parent = $reflection->getParentClass();
		return $this->getValue($parent, $instance, $field);
	}

	/**
	 * @param object $targetEntity
	 */
	protected function setCollection(Collection $toSetCollection, $targetEntity, string $field): void
	{
		$collection = new ArrayCollection($toSetCollection->toArray());

		$this->setField($targetEntity, $field, $collection);
	}

	/**
	 * @return static
	 */
	protected function set(string $key, $value): AbstractFactory
	{
		$this->set[$key] = $value;

		return $this;
	}

	/**
	 * @param       $class
	 * @param array $attributes
	 *
	 * @return object
	 */
	protected function hydrate($class, array $attributes = [])
	{
		$reflection = new ReflectionClass($class);
		$instance   = $reflection->newInstanceWithoutConstructor();

		foreach ($attributes as $field => $value) {
			$this->hydrateReflection($reflection, $instance, $field, $value);
		}

		return $instance;
	}

	/**
	 * @param ReflectionClass $reflection
	 * @param object          $instance
	 * @param string          $field
	 * @param mixed           $value
	 */
	protected function hydrateReflection(ReflectionClass $reflection, $instance, $field, $value)
	{
		if ($reflection->hasProperty($field)) {
			$property = $reflection->getProperty($field);
			$property->setAccessible(true);
			$property->setValue($instance, $value);
		} elseif ($parent = $reflection->getParentClass()) {
			self::hydrateReflection($parent, $instance, $field, $value);
		}
	}

	/**
	 * @return static
	 */
	public function notPersist(): AbstractFactory
	{
		$this->persist = false;

		return $this;
	}

	/**
	 * @return static
	 */
	public function setPersist(bool $persist): AbstractFactory
	{
		$this->persist = $persist;

		return $this;
	}
}