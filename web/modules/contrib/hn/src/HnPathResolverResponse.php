<?php

namespace Drupal\hn;

use Drupal\Core\Entity\EntityInterface;

/**
 * This class is used by PathResolvers to return an entity and status code.
 */
class HnPathResolverResponse {

  /**
   * The entity that is returnded by the path resolver.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * The status code that should be returned for this path.
   *
   * @var int
   */
  private $status;

  /**
   * HnPathResolverResponse constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param int $status
   *   The status code.
   */
  public function __construct(EntityInterface $entity, $status = 200) {
    $this->entity = $entity;
    $this->status = $status;
  }

  /**
   * The entity that is returnded by the path resolver.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * The status code that should be returned for this path.
   *
   * @return int
   *   The status code.
   */
  public function getStatus() {
    return $this->status;
  }

}
