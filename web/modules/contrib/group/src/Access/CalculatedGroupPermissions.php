<?php

namespace Drupal\group\Access;

use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * Represents a calculated set of group permissions with cacheable metadata.
 *
 * @see \Drupal\group\Access\GroupPermissionCalculator
 */
class CalculatedGroupPermissions implements CalculatedGroupPermissionsInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * A list of calculated group permission items, keyed by scope and identifier.
   *
   * @var array
   */
  protected $items = [];

  /**
   * {@inheritdoc}
   */
  public function addItem(CalculatedGroupPermissionsItemInterface $item) {
    $this->items[$item->getScope()][$item->getIdentifier()] = $item;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItem($scope, $identifier) {
    return isset($this->items[$scope][$identifier])
      ? $this->items[$scope][$identifier]
      : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    $items = [];
    foreach ($this->items as $scope_items) {
      foreach ($scope_items as $item) {
        $items[] = $item;
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsByScope($scope) {
    return isset($this->items[$scope])
      ? array_values($this->items[$scope])
      : [];
  }

  /**
   * {@inheritdoc}
   */
  public function merge(CalculatedGroupPermissionsInterface $other) {
    foreach ($other->getItems() as $theirs) {
      if ($ours = $this->getItem($theirs->getScope(), $theirs->getIdentifier())) {
        $this->addItem($this->mergeItems($ours, $theirs));
      }
      else {
        $this->addItem($theirs);
      }
    }
    $this->addCacheableDependency($other);
    return $this;
  }

  /**
   * Merges two items of identical scope and identifier.
   *
   * @param \Drupal\group\Access\CalculatedGroupPermissionsItemInterface $a
   *   The first item to merge.
   * @param \Drupal\group\Access\CalculatedGroupPermissionsItemInterface $b
   *   The second item to merge.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsItemInterface
   *   A new item representing the merger of both items.
   *
   * @throws \LogicException
   *   Exception thrown when someone somehow manages to call this method with
   *   mismatching items.
   */
  protected function mergeItems(CalculatedGroupPermissionsItemInterface $a, CalculatedGroupPermissionsItemInterface $b) {
    if ($a->getScope() !== $b->getScope()) {
      throw new \LogicException('Trying to merge two items of different scopes.');
    }

    if ($a->getIdentifier() !== $b->getIdentifier()) {
      throw new \LogicException('Trying to merge two items with different identifiers.');
    }

    $permissions = array_merge($a->getPermissions(), $b->getPermissions());
    // @todo In Group 8.2.x merge isAdmin flags and pass to constructor.
    return new CalculatedGroupPermissionsItem($a->getScope(), $a->getIdentifier(), array_unique($permissions));
  }

}
