<?php

namespace Drupal\contentserialize;

use Drupal\bulkentity\EntityLoaderInterface;

/**
 * Factory service for missing reference fixers.
 */
class MissingReferenceFixerFactory {

  /**
   * The entity loader
   *
   * @var \Drupal\bulkentity\EntityLoaderInterface
   */
  protected $entityLoader;

  /**
   * Creates a new missing reference fixer.
   *
   * @param \Drupal\bulkentity\EntityLoaderInterface $entity_loader
   *   The bulk entity loader.
   */
  public function __construct(EntityLoaderInterface $entity_loader) {
    $this->entityLoader = $entity_loader;
  }

  /**
   * Create a new missing reference fixer.
   *
   * @return \Drupal\contentserialize\MissingReferenceFixer
   */
  public function create() {
    return new MissingReferenceFixer($this->entityLoader);
  }

}
