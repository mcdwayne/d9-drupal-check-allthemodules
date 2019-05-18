<?php

namespace Drupal\purest_menus\Plugin\rest\resource;

use Symfony\Component\HttpFoundation\Request;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\system\MenuInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to retrieve menu trees.
 *
 * @RestResource(
 *   id = "purest_menu_resource",
 *   label = @Translation("Purest Menu Resource"),
 *   serialization_class = "Drupal\system\Entity\Menu",
 *   uri_paths = {
 *     "canonical" = "/purest/menu/{menu}"
 *   }
 * )
 */
class MenuResource extends ResourceBase {

  /**
   * Menu Tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The Request.
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
   * Menu Active Trail Interface.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $activeTrail;

  /**
   * Alias Manager.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * Language Manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The Current Language.
   *
   * @var string
   */
  protected $language;

  /**
   * Alias Storage Interface.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorageInterface;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $active_trail, Request $current_request, AliasManager $alias_manager, LanguageManager $language_manager, AliasStorageInterface $alias_storage_interface, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->menuTree = $menu_tree;
    $this->activeTrail = $active_trail;
    $this->request = $current_request;
    $this->alias = $this->request->query->get('alias');
    $this->aliasManager = $alias_manager;
    $this->languageManager = $language_manager;
    $this->language = $this->languageManager->getCurrentLanguage()->getId();
    $this->aliasStorageInterface = $alias_storage_interface;
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
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('path.alias_manager'),
      $container->get('language_manager'),
      $container->get('path.alias_storage'),
      $container->get('logger.factory')->get('purest_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function get(MenuInterface $menu) {
    $available_languages = $this->languageManager->getLanguages();
    $alias_language = $this->language;
    $alias_exists = FALSE;

    if ($this->alias) {
      $cache_metadata = (new CacheableMetadata())->addCacheContexts(['url.query_args']);

      foreach ($available_languages as $langcode => $language) {
        if ($this->aliasStorageInterface->aliasExists($this->alias, $langcode)) {
          $alias_exists = TRUE;
          $alias_language = $langcode;
        }
      }
    }

    if ($alias_exists) {
      $language = $this->languageManager->getLanguage($alias_language);
      $path = $this->aliasManager->getPathByAlias($this->alias, $alias_language);
      $url = Url::fromUri('internal:' . $path, ['language' => $language]);

      $active_trail_ids = $this->activeTrail->getActiveTrailIdsByRoute(
        $menu->id(), $url->getRouteName(), $url->getRouteParameters()
      );
    }
    else {
      $active_trail_ids = [];
    }

    $params = new MenuTreeParameters();
    $params->setActiveTrail($active_trail_ids);
    $tree = $this->menuTree->load($menu->id(), $params);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $tree = $this->menuTree->transform($tree, $manipulators);

    // Remove the keys to prevent reordering.
    $this->removeKeys($tree);

    $response = new ResourceResponse($tree);

    // Add menu to cache.
    $response->addCacheableDependency($menu);

    if (isset($cache_metadata)) {
      $response->addCacheableDependency($cache_metadata);
    }

    return $response;
  }

  /**
   * Remove array keys.
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
