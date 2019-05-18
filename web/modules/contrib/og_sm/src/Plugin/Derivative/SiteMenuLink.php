<?php

namespace Drupal\og_sm\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\og_sm\SiteManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides site menu links.
 *
 * @see \Drupal\og_sm\Plugin\Menu\SiteMenuLink
 */
class SiteMenuLink extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an SiteMenuLink object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, SiteManagerInterface $site_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->moduleHandler = $module_handler;
    $this->siteManager = $site_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('module_handler'),
      $container->get('og_sm.site_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Helper function to create a menu_link per og-menu instance.
   *
   * This function will return an empty array when the og_menu module is not
   * enabled.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   *
   * @return array
   *   An array of menu link definitions.
   */
  protected function createDefinitionPerOgMenu($plugin_id, array $plugin_definition) {
    if (!$this->moduleHandler->moduleExists('og_menu')) {
      return [];
    }

    $links = [];
    $instances = $this->entityTypeManager->getStorage('ogmenu_instance')->loadByProperties([
      'type' => $plugin_definition['og_menu_name'],
    ]);

    foreach ($instances as $instance) {
      $menu_name = 'ogmenu-' . $instance->id();
      $plugin_definition['menu_name'] = $menu_name;
      $links[$plugin_id . ':' . $menu_name] = $plugin_definition;
    }

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $yaml_discovery = new YamlDiscovery('site_links.menu', $this->moduleHandler->getModuleDirectories());
    $yaml_discovery->addTranslatableProperty('title', 'title_context');
    $yaml_discovery->addTranslatableProperty('description', 'description_context');

    $definitions = $yaml_discovery->getDefinitions();
    $this->moduleHandler->alter('og_sm_site_menu_links_discovered', $definitions);

    $links = [];
    foreach ($definitions as $plugin_id => $plugin_definition) {

      if (!empty($plugin_definition['parent'])) {
        $plugin_definition['parent'] = 'og_sm:' . $plugin_definition['parent'];
      }

      if (!empty($plugin_definition['og_menu_name'])) {
        $links += $this->createDefinitionPerOgMenu($plugin_id, $plugin_definition + $base_plugin_definition);
      }

      if (empty($plugin_definition['menu_name'])) {
        continue;
      }

      $links[$plugin_id] = $plugin_definition + $base_plugin_definition;
    }
    return $links;
  }

}
