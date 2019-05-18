<?php

namespace Drupal\acquia_contenthub\Event;

use Drupal\depcalc\DependentEntityWrapperInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired for publishing of entities.
 *
 * @see \Drupal\acquia_contenthub_publisher\ContentHubPublisherEvents
 */
class ContentHubPublishEntitiesEvent extends Event {

  /**
   * The key eneity uuid from which this set of entities was calculated.
   *
   * @var string
   */
  protected $keyEntityUuid;

  /**
   * The dependency wrappers.
   *
   * @var \Drupal\depcalc\DependentEntityWrapperInterface[]
   */
  protected $dependencies;

  /**
   * ContentHubPublishEntitiesEvent constructor.
   *
   * @param string $key_entity_uuid
   *   The key entity that this set of entities was calculated from.
   * @param \Drupal\depcalc\DependentEntityWrapperInterface[] $dependencies
   *   The dependency wrappers.
   */
  public function __construct($key_entity_uuid, DependentEntityWrapperInterface ...$dependencies) {
    $this->keyEntityUuid = $key_entity_uuid;
    foreach ($dependencies as $dependency) {
      $this->dependencies[$dependency->getUuid()] = $dependency;
    }
  }

  /**
   * Get the dependencies.
   *
   * @return \Drupal\depcalc\DependentEntityWrapperInterface[]
   *   The dependencies.
   */
  public function getDependencies() {
    return $this->dependencies;
  }

  /**
   * Remove a specific dependency.
   *
   * @param string $uuid
   *   The uuid of the dependency to remove.
   */
  public function removeDependency($uuid) {
    // Don't allow the key entity to be removed.
    if ($uuid != $this->keyEntityUuid) {
      unset($this->dependencies[$uuid]);
    }
  }

}
