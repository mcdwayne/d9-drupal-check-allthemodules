<?php

namespace Drupal\system_tags\SystemTagFinder;

/**
 * Interface SystemTagFinderInterface.
 *
 * @package Drupal\system_tags\SystemTagFinder
 */
interface SystemTagFinderInterface {

  /**
   * Find content by System Tag ID.
   *
   * @param string $systemTagId
   *   The ID of the System Tag.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities, tagged with the given ID.
   */
  public function findByTag($systemTagId);

  /**
   * Find a single entity by System Tag ID.
   *
   * @param string $systemTagId
   *   The ID of the System Tag.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity, tagged with the given ID or NULL if nothing is found.
   */
  public function findOneByTag($systemTagId);

}
