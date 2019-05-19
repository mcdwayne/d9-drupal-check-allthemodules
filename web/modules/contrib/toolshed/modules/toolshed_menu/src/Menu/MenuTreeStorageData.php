<?php

namespace Drupal\toolshed_menu\Menu;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Fetch data from {menu_tree} which are hard or efficient with MenuTreeStorage.
 *
 * The Drupal\Core\Menu\MenuTreeStorage class is protective and prevents
 * many of the queries that are helpful to the menu building and activities
 * related to traversing the menu tree.
 */
class MenuTreeStorageData implements ContainerInjectionInterface {

  const TABLE_NAME = 'menu_tree';

  /**
   * Names of {menu_tree} columns that are listed as serialized.
   *
   * @var string[]
   */
  protected static $serializedFields;

  /**
   * The database management service.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Initialize and prepare a new instance of the utility helper.
   *
   * @param Drupal\Core\Database\Connection $connection
   *   The database management service.
   */
  public function __construct(Connection $connection) {
    $this->db = $connection;
  }

  /**
   * Create a new instance of the utility helper from the dependency container.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The dependency injection container.
   *
   * @return Drupal\toolshed\Menu\MenuTreeStorageHelper
   *   A new instance of the MenuTreeStorageHelper utility object.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Retrieve the list of columns that are marked as serialized from the schema.
   *
   * @return string[]
   *   An array of index names of data that was stored as serialized and
   *   needs to be unserialized when formatting the data cleanly.
   */
  protected function getSerializedFields() {
    if (!isset(static::$serializedFields)) {
      try {
        // Drupal 8 creates and stores the schema for the {menu_tree} in a
        // the MenuTreeStorage class as a protected static method. This requires
        // the use of class reflection to enable us to call the method.
        $treeStoreClass = new \ReflectionClass('\Drupal\Core\Menu\MenuTreeStorage');
        $schemaMethod = $treeStoreClass->getMethod('schemaDefinition');
        $schemaMethod->setAccessible(TRUE);
        $schema = $schemaMethod->invoke(NULL);

        static::$serializedFields = [];
        foreach ($schema['fields'] as $field => $fieldDef) {
          if (!empty($fieldDef['serialize'])) {
            static::$serializedFields[$field] = $field;
          }
        }
      }
      catch (\ReflectionException $e) {
        // Prefer to discover the table columns that are serialized, but
        // fallback to the known serialized fields if they can't be found.
        static::$serializedFields = [
          'title',
          'description',
          'route_parameters',
          'options',
          'metadata',
        ];
      }
    }

    return static::$serializedFields;
  }

  /**
   * Converts data loaded from the database table into a usable data object.
   *
   * @param array $data
   *   The {menu_tree} row data.
   *
   * @return object
   *   A formatted and unserialized menu tree link data.
   */
  protected function formatMenuData(array $data) {
    $serializedFields = $this->getSerializedFields();
    $menuItem = new \stdClass();
    $menuItem->parents = [];
    $menuItem->mlid = 0;

    foreach (array_filter($data) as $fieldName => $value) {
      if (isset($serializedFields[$fieldName])) {
        $menuItem->{$fieldName} = unserialize($value);
      }
      elseif (preg_match("/^p(\d+)$/", $fieldName, $matches)) {
        $menuItem->parents[$matches[1] - 1] = $value;
      }
      else {
        $menuItem->{$fieldName} = $value;
      }
    }

    array_pop($menuItem->parents);
    return $menuItem;
  }

  /**
   * Maps {menu_tree}.mlid to {menu_tree}.id of $mlids passed in.
   *
   * Maps {menu_tree}.mlid to {menu_tree}.id of $mlids passed in. This method
   * will maintain the order of of original $mlids array. Normally, these will
   * come from the menu parents array (p1, p2, p3 ... p9).
   *
   * @param int[] $mlids
   *   An array of menu IDs to load. These are the {menu_tree}.mlid values.
   * @param bool $onlyEnabled
   *   Determines if only enabled parents get returned by this method.
   *
   * @return int[]
   *   An array of found menu IDs, with the {menu_tree}.mlid as the keys and
   *   the {menu_tree}.id as the values. Will maintain the order of the original
   *   $mlids array parameter.
   */
  public function getIdsByMlid(array $mlids, $onlyEnabled = TRUE) {
    $retval = [];

    if (!empty($mlids)) {
      $query = $this->db->select(self::TABLE_NAME)
        ->fields(self::TABLE_NAME, ['mlid', 'id'])
        ->condition('mlid', $mlids, 'IN');

      if ($onlyEnabled) {
        $query->condition('enabled', 1);
      }

      $ids = $query->execute()->fetchAllKeyed(0, 1);

      // Add the retrieved IDs in the order originally passed in.
      // This is especially important if the order is a trail to the root.
      foreach ($mlids as $mlid) {
        if (isset($ids[$mlid])) {
          $retval[$mlid] = $ids[$mlid];
        }
      }
    }

    return $retval;
  }

  /**
   * Load a single menu item matching the ID.
   *
   * @param string $id
   *   The menu tree ID to load.
   *
   * @return object|null
   *   A menu object in the format returned by self::formateMenuData()
   *   or null if a matching menu couldn't be found.
   */
  public function load($id) {
    $data = $this->db->select(self::TABLE_NAME)
      ->fields(self::TABLE_NAME)
      ->condition('id', $id)
      ->execute()
      ->fetchAssoc();

    if ($data) {
      return $this->formatMenuData($data);
    }
  }

  /**
   * Create an object which represents the root of a menu tree.
   *
   * Create a consistent representation of the root menu item for a menu. This
   * is really a dummy menu item, but to ensure a consistent format and an
   * easy way to request the menu root returns an menu item with mlid = 0 and
   * id = <root>.
   *
   * @param string $menu_name
   *   Machine name of the menu to get a root menu item representation for.
   *
   * @return Object
   *   A menu object that represents the root of a menu tree.
   */
  public function getMenuRoot($menu_name) {
    $menuItem = new \stdClass();
    $menuItem->menu_name = $menu_name;
    $menuItem->id = '<root>';
    $menuItem->mlid = 0;
    $menuItem->parent = '';
    $menuItem->parents = [];

    return $menuItem;
  }

  /**
   * Retrieves {menu_tree} rows that match a route.
   *
   * @param Drupal\Core\Routing\RouteMatchInterface $route
   *   The route information to use when searching for matching menu link items.
   * @param string[] $menus
   *   An array of menu machine names that are allowed in the results. Unlike
   *   the query by MenuTreeStorage::loadByRoute(), this function allows
   *   and array of menu names.
   *
   * @return object[]
   *   An array of matching menu objects in the format returned by
   *   self::formateMenuData().
   */
  public function loadItemsByRoute(RouteMatchInterface $route, array $menus = []) {
    $routeName = $route->getRouteName();
    $routeParams = $route->getRawParameters()->all();

    asort($routeParams);

    $query = $this->db->select(self::TABLE_NAME)
      ->fields(self::TABLE_NAME)
      ->condition('route_name', $routeName)
      ->condition('route_param_key', UrlHelper::buildQuery($routeParams))
      ->orderBy('depth')
      ->orderBy('weight');

    if (!empty($menus)) {
      if (count($menus) === 1) {
        $query->condition('menu_name', reset($menus));
      }
      else {
        $query->condition('menu_name', $menus, 'IN');
      }
    }

    try {
      $results = [];

      $rs = $query->execute();
      while ($result = $rs->fetchAssoc()) {
        $results[$result['id']] = $this->formatMenuData($result);
      }

      return $results;
    }
    catch (\Exception $e) {
      throw $e;
    }
  }

}
