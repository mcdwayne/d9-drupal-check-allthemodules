<?php

namespace Drupal\group\Access;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;

/**
 * Defines the calculated group permissions interface.
 */
interface CalculatedGroupPermissionsInterface extends RefinableCacheableDependencyInterface {

  /**
   * Adds a calculated permission item.
   *
   * @param \Drupal\group\Access\CalculatedGroupPermissionsItemInterface $item
   *   The calculated permission item.
   *
   * @return $this
   */
  public function addItem(CalculatedGroupPermissionsItemInterface $item);

  /**
   * Retrieves a single calculated permission item from a given scope.
   *
   * @param $scope
   *   The scope name to retrieve the item for.
   * @param $identifier
   *   The scope identifier to retrieve the item for.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsItemInterface
   *   The calculated permission item.
   */
  public function getItem($scope, $identifier);

  /**
   * Retrieves all of the calculated permission items, regardless of scope.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsItemInterface[]
   *   A list of calculated permission items.
   */
  public function getItems();

  /**
   * Retrieves all of the calculated permission items for the given scope.
   *
   * @param string $scope
   *   The scope name to retrieve the items for.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsItemInterface[]
   *   A list of calculated permission items for the given scope.
   */
  public function getItemsByScope($scope);

  /**
   * Merge another calculated group permissions object into this one.
   *
   * This merges (not replaces) all permissions and cacheable metadata.
   *
   * @param \Drupal\group\Access\CalculatedGroupPermissionsInterface $other
   *   The other calculated group permissions object to merge into this one.
   *
   * @return $this
   */
  public function merge(CalculatedGroupPermissionsInterface $other);

}
