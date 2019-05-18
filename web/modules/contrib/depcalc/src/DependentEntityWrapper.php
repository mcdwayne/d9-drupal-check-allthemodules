<?php

namespace Drupal\depcalc;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;

class DependentEntityWrapper implements DependentEntityWrapperInterface {

  /**
   * The entity id.
   *
   * @var int|null|string
   */
  protected $id;

  /**
   * The entity type id.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity uuid.
   *
   * @var null|string
   */
  protected $uuid;

  /**
   * The sha1 hash value of the entity.
   *
   * @var string
   */
  protected $hash;

  /**
   * The remote uuid of the entity if it differs from the ContentHub uuid.
   *
   * @var string
   */
  protected $remoteUuid;

  /**
   * The list of uuid/hash values of dependencies of this entity.
   *
   * @var string[]
   */
  protected $dependencies = [];

  /**
   * The modules this entity requires to operate.
   *
   * @var string[]
   */
  protected $modules = [];

  /**
   * Whether this entity needs additional processing.
   *
   * @var bool
   */
  protected $additionalProcessing;


  /**
   * DependentEntityWrapper constructor.
   *
   * The entity object is thrown away within this constructor and just the bare
   * minimum of details to reconstruct it are kept. This is to reduce memory
   * overhead during the run time of dependency calculation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which we are calculating dependencies.
   *
   * @throws \Exception
   */
  public function __construct(EntityInterface $entity, $addition_processing = FALSE) {
    $this->entityTypeId = $entity->getEntityTypeId();
    $this->id = $entity->id();
    $uuid = $entity->uuid();
    $this->hash = sha1(json_encode($entity->toArray()));
    if (empty($uuid)) {
      throw new \Exception(sprintf("The entity of type %s by id %s does not have a UUID. This indicates a larger problem with your application and should be remedied before attempting to calculate dependencies.", $this->entityTypeId, $this->id));
    }
    $this->uuid = $uuid;
    $this->additionalProcessing = $addition_processing;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return \Drupal::service("entity.repository")->loadEntityByUuid($this->getEntityTypeId(), $this->getUuid());
  }

  public function setRemoteUuid($uuid) {
    $this->remoteUuid = $uuid;
  }

  public function getRemoteUuid() {
    if (!empty($this->remoteUuid)) {
      return $this->remoteUuid;
    }
    return $this->getUuid();
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function addDependency(DependentEntityWrapperInterface $dependency, DependencyStack $stack) {
    // Don't save a thing as a dependency of itself.
    if ($dependency->getUuid() == $this->getUUid()) {
      return;
    }
    if (!array_key_exists($dependency->getUuid(), $this->dependencies)) {
      $this->dependencies[$dependency->getUuid()] = $dependency->getHash();
      if (!$stack->hasDependency($dependency->getUuid())) {
        $stack->addDependency($dependency);
        foreach ($stack->getDependenciesByUuid(array_keys($dependency->getDependencies())) as $sub_dependency) {
          $this->addDependency($sub_dependency, $stack);
        }
      }
      else {
        $this->addDependencies($stack, ...array_values($stack->getDependenciesByUuid(array_keys($stack->getDependency($dependency->getUuid())->getDependencies()))));
      }
      $modules = $stack->getDependency($dependency->getUuid())->getModuleDependencies();
      if ($modules) {
        $this->addModuleDependencies($modules);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addDependencies(DependencyStack $stack, DependentEntityWrapperInterface ...$dependencies) {
    foreach ($dependencies as $dependency) {
      $this->addDependency($dependency, $stack);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function addModuleDependencies(array $modules) {
    $this->modules = array_values(array_unique(NestedArray::mergeDeep($this->modules, $modules)));
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleDependencies() {
    return $this->modules;
  }

  /**
   * {@inheritdoc}
   */
  public function getHash() {
    return $this->hash;
  }

  public function needsAdditionalProcessing() {
    return $this->additionalProcessing;
  }

}
