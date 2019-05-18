<?php

/**
 * @file
 * Contains \Drupal\colossal_menu\Menu\MenuTreeStorage.
 */

namespace Drupal\colossal_menu\Menu;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuTreeStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Provides a menu tree storage using the database.
 */
class MenuTreeStorage implements MenuTreeStorageInterface {

  /**
   * The maximum depth of a menu links tree.
   *
   * This storage has no theoretical limit, but we'll set a reasonable limit.
   */
  const MAX_DEPTH = 20;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The database table name.
   *
   * @var string
   */
  protected $table;

  /**
   * Constructs a new \Drupal\Core\Menu\MenuTreeStorage.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection to use for reading and writing configuration data.
   * @param string $table
   *   A database table name to store configuration data in.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $current_route_match, $entity_type, $table) {
    $this->connection = $connection;
    $this->storage = $entity_type_manager->getStorage($entity_type);
    $this->currentRouteMatch = $current_route_match;
    $this->table = $table;
  }

  /**
   * {@inheritdoc}
   */
  public function maxDepth() {
    return self::MAX_DEPTH;
  }

  /**
   * {@inheritdoc}
   *
   * Allow the entity system to cache the results.
   */
  public function resetDefinitions() {}

  /**
   * {@inheritdoc}
   *
   * Allow the entity system to cache the results.
   */
  public function rebuild(array $definitions) {}

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    return $this->storage->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    return $this->storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $properties) {
    return $this->storage->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByRoute($route_name, array $route_parameters = [], $menu_name = NULL) {
    $url = new Url($route_name, $route_parameters);

    $query = $this->storage->getQuery();
    $query->condition('link__uri', $url->getUri());

    if ($menu_name) {
      $query->condition('menu', $menu_name);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $definition) {
    return $this->storage->create($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
    return $this->storage->delete($this->storage->load($id));
  }

  /**
   * {@inheritdoc}
   */
  public function loadTreeData($menu_name, MenuTreeParameters $parameters) {
    $query = $this->connection->select($this->table, 't')
      ->fields('t', ['ancestor', 'descendant', 'depth'])
      ->condition('e.menu', $menu_name)
      // The order is important!
      ->orderBy('t.depth', 'ASC')
      ->orderBy('e.weight', 'ASC');

    $query->innerJoin($this->storage->getEntityType()->get('base_table'), 'e', 't.ancestor = e.id');

    if ($parameters->root) {
      $query->condition('t.ancestor', $parameters->root);
    }

    if ($parameters->minDepth > 1) {
      // Since the default depth is 1, and in our storage it's 0, we'll
      // decrement the minimum depth.
      $query->condition('t.depth', '>=', $parameters->minDepth - 1);
    }

    if ($parameters->maxDepth) {
      $query->condition('t.depth', '<=', $parameters->maxDepth);
    }

    $result = $query->execute();

    $flat = [];
    $depth = [];
    while ($row = $result->fetchObject()) {
      $flat[$row->ancestor][] = $row->descendant;
      if (isset($depth[$row->descendant]) && $row->depth > $depth[$row->descendant]) {
        $depth[$row->descendant] = $row->depth;
      }
      elseif (!isset($depth[$row->descendant])) {
        $depth[$row->descendant] = $row->depth;
      }
    }

    $links = $this->loadMultiple(array_keys($flat));

    $routes = [];
    foreach ($links as $link) {
      if (!$link->isExternal() && $name = $link->getRouteName()) {
        $routes[$link->id()] = $name;
      }
    }

    $tree = $this->treeDataRecursive($flat, $links, $depth, $routes);

    return [
      'tree' => $tree,
      'route_names' => $routes,
    ];
  }

  /**
   * Build the tree from the closure table.
   *
   * @param array $flat
   *   A flat tree returned from the database.
   * @param array $links
   *   An array of Link objects.
   * @param array $depth
   *   An array of depth values.
   * @param array $routes
   *   An array of route names.
   *
   * @return array
   *   A fully-formed link tree.
   */
  protected function treeDataRecursive(array $flat, array $links, array $depth, array $routes) {
    uasort($flat, function($a, $b) {
      return count($a) - count($b);
    });

    $tree = [];
    foreach ($flat as $id => $decendents) {
      foreach ($decendents as $decendent) {
        if ($id == $decendent) {
          $active = FALSE;
          if (isset($routes[$id]) && $this->currentRouteMatch->getRouteName() == $routes[$id]) {
            $active = TRUE;
          }

          $tree[$id] = [
            'link' => $links[$id],
            'has_children' => FALSE,
            'subtree' => [],
            'depth' => $depth[$id] + 1,
            'in_active_trail' => $active,
          ];
        }
        else {
          if (isset($tree[$decendent])) {
            $tree[$id]['has_children'] = TRUE;
            $tree[$id]['in_active_trail'] = $tree[$decendent]['in_active_trail'];
            $tree[$id]['subtree'][$decendent] = $tree[$decendent];
            unset($tree[$decendent]);

            if (count($tree[$id]['subtree']) > 1) {
              uasort($tree[$id]['subtree'], function($a, $b) {
                return ($a['link']->getWeight() < $b['link']->getWeight()) ? -1 : 1;
              });
            }
          }
        }
      }
    }

    if (count($tree) > 1) {
      uasort($tree, function($a, $b) {
        return ($a['link']->getWeight() < $b['link']->getWeight()) ? -1 : 1;
      });
    }

    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllChildren($id, $max_relative_depth = NULL) {
    $query = $this->connection->select($this->table, 't');
    $query->fields('t', ['descendant']);
    $query->condition('t.ancestor', $id);

    if ($max_relative_depth) {
      $query->condition('t.depth', '<=', $max_relative_depth);
    }

    $query->orderBy('t.depth', 'ASC');

    $ids = $query->execute()->fetchCol();

    return $this->storage->getQuery()
      ->condition('id', $ids)
      ->orderBy('weight', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function getAllChildIds($id) {
    return $this->connection->select($this->table, 't')
      ->fields('t', ['descendant'])
      ->condition('t.ancestor', $id)
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function loadSubtreeData($id, $max_relative_depth = NULL) {
    $link = $this->load($id);
    $params = new MenuTreeParameters();
    $params->root = $id;
    $params->setMaxDepth($max_relative_depth);
    return $this->loadTreeData($link->getMenuName(), $params);
  }

  /**
   * {@inheritdoc}
   */
  public function getRootPathIds($id) {
    return $this->connection->select($this->table, 't')
      ->fields('t', ['ancestor'])
      ->condition('t.descendant', $id)
      ->orderBy('t.depth', 'DESC')
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function getExpanded($menu_name, array $parents) {
    $query = $this->connection->select($this->table, 't')
      ->fields('t', ['descendant'])
      ->condition('t.ancestor', $parents)
      ->condition('e.menu', $menu_name)
      ->orderBy('t.depth', 'ASC')
      ->orderBy('e.weight', 'ASC');
    $query->innerJoin($this->storage->getEntityType()->get('base_table'), 'e', 't.ancestor = e.id');
    return $query->execute()->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtreeHeight($id) {
    return $this->conneciton->select($this->table, 't')
      ->fields('t', ['depth'])
      ->condition('t.descendant', $id)
      ->orderBy('t.depth', 'DESC')
      ->limit(0, 1)
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function menuNameInUse($menu_name) {
    $links = $this->storage->loadByProperties([
      'menu' => $menu_name,
    ]);

    return empty($links);
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuNames() {
    return $this->connection->select($this->storage->getEntityType()->get('base_table'), 'e')
      ->distinct()
      ->fields('e', ['menu'])
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countMenuLinks($menu_name = NULL) {
    $query = $this->connection->select($this->storage->getEntityType()->get('base_table'), 'e')
      ->count();

    if ($menu_name) {
      $query->condition('e.menu', $menu_name);
    }

    return $query->execute();
  }

}
