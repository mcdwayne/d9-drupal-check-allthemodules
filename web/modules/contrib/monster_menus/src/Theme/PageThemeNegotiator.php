<?php

namespace Drupal\monster_menus\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets the current theme based on the MM page.
 *
 * Class PageThemeNegotiator
 *
 * @package Drupal\monster_menus\Theme
 */
class PageThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a PageThemeNegotiator object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_obj = $route_match->getRouteObject();
    return $route_obj && strpos($route_obj->getPath(), '{mm_tree}') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $mmtid = $route_match->getParameter('mm_tree');
    $mmtid = is_object($mmtid) ? $mmtid->id() : intval($mmtid);
    $mmtids = mm_content_get_parents_with_self($mmtid);
    if ($mmtids) {
      $select = $this->database->select('mm_tree', 't');
      $select->fields('t', array('theme'));
      $select->addExpression('LENGTH(sort_idx)', 'tree_depth');
      $select->condition('t.mmtid', $mmtids, 'IN')
        ->condition('t.theme', '', '<>')
        ->orderBy('tree_depth', 'DESC')
        ->range(0, 1);
      if ($theme = $select->execute()->fetchField()) {
        return $theme;
      }
    }
    return NULL;
  }

}