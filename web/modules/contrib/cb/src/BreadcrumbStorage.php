<?php

namespace Drupal\cb;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a Controller class for chained breadcrumbs.
 */
class BreadcrumbStorage extends SqlContentEntityStorage implements BreadcrumbStorageInterface {

  /**
   * Array of all loaded breadcrumb ancestry keyed by ancestor breadcrumb ID.
   *
   * @var array
   */
  protected $allParents = [];

  /**
   * Array of child breadcrumbs keyed by parent breadcrumb ID.
   *
   * @var array
   */
  protected $children = [];

  /**
   * {@inheritdoc}
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $query = parent::buildQuery($ids, $revision_id);
    $query->join('cb_breadcrumb_field_data', 'cb', "base.bid = cb.bid");
    // Collect all direct children (without sub children) of the breadcrumb and order its by fields - weight and name.
    $query->addExpression("(SELECT GROUP_CONCAT(bid ORDER BY weight, name) FROM {cb_breadcrumb_field_data} ecb WHERE ecb.parent = cb.bid)", 'children');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    $this->allParents = [];
    $this->children = [];
    parent::resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllParents($bid) {
    if (!isset($this->allParents[$bid])) {
      $parents = [];
      if ($breadcrumb = $this->load($bid)) {
        $parents[$breadcrumb->id()] = $breadcrumb;
        if ($breadcrumb->hasParent()) {
          $parents += $this->loadAllParents($breadcrumb->getParentId());
        }
      }

      $this->allParents[$bid] = $parents;
    }

    return $this->allParents[$bid];
  }

  /**
   * {@inheritdoc}
   */
  public function loadLevel($bid, $depth = 0) {
    if (!isset($this->level[$bid])) {
      $query = $this->database->select('cb_breadcrumb_field_data', 'cb');
      $query->addField('cb', 'parent');
      $query->condition('bid', $bid);
      $parent = $query->execute()->fetchField();
      if ($parent > 0) {
        $depth = $this->loadLevel($parent, $depth + 1);
      }

      $this->level[$bid] = $depth;
    }

    return $this->level[$bid];
  }

  /**
   * {@inheritdoc}
   */
  public function loadChildren($bid) {
    if (!isset($this->children[$bid])) {
      $children = [];
      if ($breadcrumb = $this->load($bid)) {
        $children[$breadcrumb->id()] = $breadcrumb;
        if ($breadcrumb->hasChildren()) {
          foreach ($breadcrumb->getChildrenIds() as $id) {
            $children += $this->loadChildren($id);
          }
        }
      }

      $this->children[$bid] = $children;
    }

    return $this->children[$bid];
  }

  /**
   * {@inheritdoc}
   */
  public function buildTree() {
    $tree = [];
    $query = db_select('cb_breadcrumb_field_data', 'cb');
    $query->addField('cb', 'bid');
    $query->condition('parent', 0);
    $query->orderBy('weight');
    $query->orderBy('name');
    $ids = $query->execute()->fetchCol();
    $parents = $this->loadMultiple($ids);
    if ($parents) {
      foreach ($parents as $parent) {
        $depth = 1;
        $tree[$parent->id()] = [
          'breadcrumb' => $parent,
          'children' => $this->buildChildrenBranch($parent, $depth + 1),
          'depth' => $depth,
        ];
      }
    }

    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function buildChildrenBranch($breadcrumb, $depth) {
    $children = [];
    if ($ids = $breadcrumb->getChildrenIds()) {
      foreach ($ids as $id) {
        $child = $this::load($id);
        $children[$id] = [
          'breadcrumb' => $child,
          'children' => $this->buildChildrenBranch($child, $depth + 1),
          'depth' => $depth,
        ];
      }
    }

    return $children;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptions(array $tree, $indent, array &$options, $exclude) {
    foreach ($tree as $element) {
      $breadcrumb = $element['breadcrumb'];
      if ($breadcrumb->id() != $exclude) {
        $title = $indent . ' ' . Unicode::truncate($breadcrumb->getName(), 30, TRUE, FALSE);
        $options[$breadcrumb->id()] = $title;
        if (!empty($element['children'])) {
          $this->buildOptions($element['children'], $indent . '-', $options, $exclude);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $vars = parent::__sleep();
    // Do not serialize static cache.
    unset($vars['allParents'], $vars['children']);
    return $vars;
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    parent::__wakeup();
    // Initialize static caches.
    $this->allParents = [];
    $this->children = [];
  }

}
