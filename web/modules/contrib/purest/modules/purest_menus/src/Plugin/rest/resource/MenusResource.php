<?php

namespace Drupal\purest_menus\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a resource to retrieve menus defined in configuration.
 *
 * @RestResource(
 *   id = "purest_menus_resource",
 *   label = @Translation("Purest Menus Resource"),
 *   serialization_class = "Drupal\system\Entity\Menu",
 *   uri_paths = {
 *     "canonical" = "/purest/menus"
 *   }
 * )
 */
class MenusResource extends ResourceBase {

  /**
   * Menu Tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Path Alias.
   *
   * @var string
   */
  protected $alias;

  /**
   * MenuActiveTrailInterface.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $activeTrail;

  /**
   * AliasManager.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * LanguageManager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Language ID.
   *
   * @var int
   */
  protected $language;

  /**
   * AliasStorageInterface.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorageInterface;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $active_trail, EntityTypeManagerInterface $entity_type_manager, Request $current_request, AliasManager $alias_manager, LanguageManager $language_manager, AliasStorageInterface $alias_storage_interface, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->menuTree = $menu_tree;
    $this->activeTrail = $active_trail;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityStorage = $this->entityTypeManager->getStorage('menu');
    $this->request = $current_request;
    $this->alias = $this->request->query->get('alias');
    $this->aliasManager = $alias_manager;
    $this->languageManager = $language_manager;
    $this->language = $this->languageManager->getCurrentLanguage()->getId();
    $this->aliasStorageInterface = $alias_storage_interface;
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('purest_menus.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail'),
      $container->get('entity_type.manager'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('path.alias_manager'),
      $container->get('language_manager'),
      $container->get('path.alias_storage'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    $available_languages = $this->languageManager->getLanguages();
    $alias_language = $this->language;
    $exists = FALSE;
    $menus = [];
    $raw_menus = [];
    $menu_ids = $this->config->get('menus');

    if ($menu_ids) {
      if ($this->alias) {
        $cache_metadata = (new CacheableMetadata())->addCacheContexts(['url.query_args']);

        foreach ($available_languages as $langcode => $language) {
          if ($this->aliasStorageInterface->aliasExists($this->alias, $langcode)) {
            $alias_exists = TRUE;
            $alias_language = $langcode;
            $language = $this->languageManager->getLanguage($alias_language);
            $path = $this->aliasManager->getPathByAlias($this->alias, $alias_language);
            $url = Url::fromUri('internal:' . $path, ['language' => $language]);
          }
        }
      }

      foreach ($menu_ids as $menu_id) {
        $menu = $this->entityStorage->load($menu_id['target_id']);

        if ($exists) {
          $active_trail_ids = $this->activeTrail->getActiveTrailIdsByRoute(
            $menu->id(), $url->getRouteName(), $url->getRouteParameters()
          );
        }
        else {
          $active_trail_ids = [];
        }

        // Store the raw menu objects to add as cacheable dependencies.
        $raw_menus[] = $menu;

        $params = new MenuTreeParameters();
        $params->setActiveTrail($active_trail_ids);
        $tree = $this->menuTree->load($menu->id(), $params);

        $manipulators = [
          ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
          ['callable' => 'menu.default_tree_manipulators:checkAccess'],
          ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ];

        $tree = $this->menuTree->transform($tree, $manipulators);
        $this->removeKeys($tree);
        $menus[str_replace('-', '_', $menu_id['target_id'])] = $tree;
      }
    }

    $response = new ResourceResponse($menus);
    $response->addCacheableDependency($this->config);

    if (isset($cache_metadata)) {
      $response->addCacheableDependency($cache_metadata);
    }

    foreach ($raw_menus as $menu) {
      $response->addCacheableDependency($menu);
    }

    return $response;
  }

  /**
   * Remove useless array keys.
   */
  protected function removeKeys(array &$data) {
    $tree = [];
    foreach ($data as $value) {
      if ($value->subtree) {
        $this->removeKeys($value->subtree);
      }

      $tree[] = $value;
    }

    $data = $tree;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);

    $parameters = $route->getOption('parameters') ?: [];
    $parameters['menu']['type'] = 'entity:menu';
    $route->setOption('parameters', $parameters);

    return $route;
  }

}
