<?php

namespace Drupal\toolshed_menu\Plugin\Toolshed\MenuResolver;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\toolshed_menu\Menu\MenuTreeStorageData;
use Drupal\toolshed_menu\MenuResolver\MenuResolverInterface;

/**
 * The default menu resolver uses current routing match as the active menu item.
 *
 * @MenuResolver(
 *   id = "default_menu_resolver",
 *   label = @Translation("Default Menu Resolver"),
 *   help = @Translation("Matches menu the current route name and parameters."),
 * )
 */
class DefaultMenuResolver extends PluginBase implements MenuResolverInterface, ContainerFactoryPluginInterface {

  /**
   * Service for managing entity types, and handlers.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Route information to use when resolving the menu link to use.
   *
   * @var Drupal\Core\Routing\RouteMatchInterface
   */
  protected $route;

  /**
   * The error logging service channel for the Toolshed Menu module.
   *
   * @var Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Generate a new instance of the default menu link resolving plugin.
   *
   * @param array $configuration
   *   Instance configuration to use to construct this instance of the
   *   menu resolution plugin class.
   * @param string $pluginId
   *   The ID to use for this plugin.
   * @param mixed $pluginDef
   *   Information about the plugin gathered from the plugin discovery.
   * @param Drupal\Core\Database\Connection $connection
   *   The menu tree storage service.
   * @param Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The routing information to use to determine what menu item is active.
   * @param Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger creating factory.
   */
  public function __construct(array $configuration, $pluginId, $pluginDef, Connection $connection, RouteMatchInterface $routeMatch, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $pluginId, $pluginDef);

    $this->db = $connection;
    $this->route = $routeMatch;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('logger.factory')->get('toolshed_menu')
    );
  }

  /**
   * Determine which menu link to use, resolver has several canidates.
   *
   * Contains the logic to determine which menu item should be used when
   * multiple options are available. Different menu resolvers are able to
   * use different rules to decide which menu item wins out.
   *
   * This version just looks for the first menu, in order of menu preference,
   * for the first menu item in that menu.
   *
   * @param object[] $menuItems
   *   An array of loaded menu items. Menu items contains data as returned
   *   by the MenuTreeStorage class.
   * @param string[] $menuNames
   *   An array of menu names in order of precedence.
   *
   * @return array
   *   Return the array data for a single menu link. This link will be used
   *   to determine what menu tree to render and load.
   */
  protected function determineMenuItem(array $menuItems, array $menuNames = []) {
    $score = PHP_INT_MAX;
    $menuItem = NULL;
    $menuLookup = array_flip(array_values($menuNames));

    foreach ($menuItems as $item) {
      if (!isset($menuLookup[$item->menu_name])) {
        continue;
      }

      if ($menuLookup[$item->menu_name] === 0) {
        return $item;
      }
      elseif ($score > $menuLookup[$item->menu_name]) {
        $score = $menuLookup[$item->menu_name];
        $menuItem = $item;
      }
    }

    return $menuItem;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(array $menuNames = [], RouteMatchInterface $route = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(array $menuNames = [], RouteMatchInterface $route = NULL) {
    // Most sites will only need to cache on the route, but some
    // might have menu specific logic related to the query arguments.
    // In those cases, you should override this menu resolver.
    return ['route'];
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(array $menuNames = [], RouteMatchInterface $route = NULL) {
    $route = $route ?: $this->route;

    $menuStorage = new MenuTreeStorageData($this->db);
    $menuLinks = $menuStorage->loadItemsByRoute($route, $menuNames);
    $menuLink = count($menuLinks) > 1 ? $this->determineMenuItem($menuLinks, $menuNames) : reset($menuLinks);

    return $menuLink;
  }

}
