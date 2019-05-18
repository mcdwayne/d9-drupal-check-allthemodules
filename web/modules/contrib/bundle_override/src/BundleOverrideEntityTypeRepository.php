<?php

namespace Drupal\bundle_override;

use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\bundle_override\Manager\Objects\BundleOverrideObjectsInterface;

/**
 * Entity type respository service to allow bundle classes to load entities.
 */
class BundleOverrideEntityTypeRepository extends EntityTypeRepository implements EntityTypeRepositoryInterface {

  /**
   * The original service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepository\EntityTypeRepositoryInterface
   */
  protected $inner;

  /**
   * Constructs a new BundleOverrideEntityTypeRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $inner
   *   The original service we're overriding.
   */
  public function __construct(EntityTypeRepositoryInterface $inner) {
    $this->inner = $inner;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels($group = FALSE) {
    return $this->inner->getEntityTypeLabels($group);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeFromClass($class_name) {
    $class = new \ReflectionClass($class_name);
    if ($class->implementsInterface(BundleOverrideObjectsInterface::class)) {
      return $class_name::getStaticEntityTypeid();
    }

    return $this->inner->getEntityTypeFromClass($class_name);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    return $this->inner->clearCachedDefinitions();
  }

}
