<?php

namespace Drupal\cb;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for cb_breadcrumb entity storage classes.
 */
interface BreadcrumbStorageInterface extends ContentEntityStorageInterface {

  /**
   * Recursively finds all ancestors of a breadcrumb ID.
   *
   * @param int $bid
   *   Breadcrumb ID to retrieve ancestors for.
   *
   * @return \Drupal\cb\BreadcrumbInterface[]
   *   An array of breadcrumb objects which are the ancestors of the breadcrumb $bid.
   */
  public function loadAllParents($bid);

  /**
   * Recursively finds all children of a breadcrumb ID.
   *
   * @param int $tid
   *   Breadcrumb ID to retrieve children for.
   *
   * @return \Drupal\cb\BreadcrumbInterface[]
   *   An array of breadcrumb objects that are the children of the breadcrumb $tid.
   */
  public function loadChildren($bid);

  /**
   * @todo describe method.
   */
  public function loadLevel($bid);

}
