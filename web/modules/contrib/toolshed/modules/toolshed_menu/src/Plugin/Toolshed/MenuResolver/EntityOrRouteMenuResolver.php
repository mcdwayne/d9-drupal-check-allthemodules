<?php

namespace Drupal\toolshed_menu\Plugin\Toolshed\MenuResolver;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\toolshed_menu\Menu\MenuTreeStorageData;

/**
 * The default menu resolver uses current routing match as the active menu item.
 *
 * @MenuResolver(
 *   id = "entity_route_menu_resolver",
 *   label = @Translation("Entity or Route Menu Resolver"),
 *   help = @Translation("Find menu first based on entity type menu settings, but defaults to using the current route."),
 * )
 */
class EntityOrRouteMenuResolver extends DefaultMenuResolver {

  /**
   * The Drupal service for managing different entity types and their handlers.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

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
   * @param Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The services that manages all the entity types and their handlers.
   */
  public function __construct(array $configuration, $pluginId, $pluginDef, Connection $connection, RouteMatchInterface $routeMatch, LoggerChannelInterface $logger, EntityTypeManager $entityTypeManager) {
    parent::__construct($configuration, $pluginId, $pluginDef, $connection, $routeMatch, $logger);

    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('logger.factory')->get('toolshed_menu'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(array $menuNames = [], RouteMatchInterface $route = NULL) {
    $route = $route ?: $this->route;
    $routeName = $route->getRouteName();

    if (preg_match('#^entity\.([\w_]+)\.canonical$#', $routeName, $matches)) {
      $entity = $route->getParameter($matches[1]);

      // This cache is invalidated if potentially this bundle is updated with
      // new menu information that could change the results of the resolution.
      if ($entity) {
        $bundleType = $entity->getEntityType()->getBundleEntityType();
        return [$bundleType . ':' . $entity->bundle()];
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(array $menuNames = [], RouteMatchInterface $route = NULL) {
    $menuItem = NULL;
    $route = $route ?: $this->route;
    $routeName = $route->getRouteName();

    if (preg_match('#^entity\.([\w_]+)\.canonical$#', $routeName, $matches)) {
      $entity = $route->getParameter($matches[1]);

      if ($entity instanceof ContentEntityInterface && ($entityMenus = $this->getEntityBundleMenuSettings($entity))) {
        foreach ($menuNames as $menu) {
          if (!empty($entityMenus[$menu])) {
            $menuTreeData = new MenuTreeStorageData($this->db);
            $menuItem = $menuTreeData->load($entityMenus[$menu]);
          }
        }
      }
    }

    return $menuItem ?: parent::resolve($menuNames, $route);
  }

  /**
   * Fetch an array menu items match this entity.
   *
   * @param Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to search a menu link reference for. The menu
   *   link will exist based on the menu name, and resides in the
   *   third party settings under Toolshed Menu module.
   *
   * @return array|null
   *   An array of selected menu items that match for this entity.
   */
  protected function getEntityBundleMenuSettings(ContentEntityInterface $entity) {
    $bundleType = $entity->getEntityType()->getBundleEntityType();
    try {
      $bundle = !empty($bundleType) && $bundleType !== $entity->getEntityTypeId()
        ? $this->entityTypeManager->getStorage($bundleType)->load($entity->bundle()) : $entity;

      if ($bundle instanceof ThirdPartySettingsInterface) {
        return $bundle->getThirdPartySetting('toolshed_menu', 'entity_menu', NULL);
      }
    }
    catch (\Exception $e) {
      $this->logger->get('toolshed_menu')->error($e->getMessage(), ['exception' => $e]);
    }
  }

}
