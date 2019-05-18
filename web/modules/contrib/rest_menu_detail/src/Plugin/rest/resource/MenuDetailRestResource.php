<?php

namespace Drupal\rest_menu_detail\Plugin\rest\resource;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "menu_detail_rest_resource",
 *   label = @Translation("Menu detail rest resource"),
 *   uri_paths = {
 *     "canonical" = "/api/menu_detail/{menuName}"
 *   }
 * )
 */
class MenuDetailRestResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * A instance of entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * A list of menu items.
   *
   * @var array
   */
  protected $menuDetails = [];

  /**
   * Constructs a new MenuDetailRestResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   A instance of entity manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityManagerInterface $entity_manager,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
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
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($menuName = NULL) {

    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    if ($menuName) {

      // Create the parameters.
      $parameters = new MenuTreeParameters();
      $parameters->onlyEnabledLinks();

      // Load the tree based on this set of parameters.
      $menu_tree_detail = \Drupal::menuTree();
      $tree = $menu_tree_detail->load($menuName, $parameters);

      // Return if the menu does not exist or has no entries.
      if (empty($tree)) {
        return new ResourceResponse($tree);
      }

      // Transform the tree using the manipulators you want.
      $manipulators = [
        // Only show links that are accessible for the current user.
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        // Use the default sorting of menu links.
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $tree = $menu_tree_detail->transform($tree, $manipulators);

      // Finally, build a renderable array from the transformed tree.
      $menu = $menu_tree_detail->build($tree);

      // Get the setting to check which parameter should be part of response.
      $menuSettings = \Drupal::config('rest_menu_detail.menurestsettings')->get('select_parameters');

      $menuSettings = $menuSettings ? $menuSettings : [];

      $this->menuTreeDetails($menuSettings, $menu['#items'], $this->menuDetails);

      // Return response.
      $response = new ResourceResponse(array_values($this->menuDetails));
      $response->addCacheableDependency($this->menuDetails);

      return $response;
    }
    throw new HttpException(t("Menu name is required parameter"));
  }

  /**
   * Generate the menu tree we can use in JSON.
   *
   * @param array $menuSettings
   *   Menu COnfiguration as per required parameter in response.
   * @param array $transformed_tree
   *   The menu tree.
   * @param array $menu_tree
   *   The already created items.
   */
  protected function menuTreeDetails(array $menuSettings, array $transformed_tree, array &$menu_tree = []) {
    $order = 0;
    foreach ($transformed_tree as $menu_item) {
      $menu_link = $menu_item['original_link'];
      /* @var $url \Drupal\Core\Url */
      $url = $menu_item['url'];

      $external = FALSE;
      $uuid = '';
      if ($url->isExternal()) {
        $uri = $url->getUri();
        $external = TRUE;
        $absolute = $uri;
      }
      else {
        try {
          $uri = $url->getInternalPath();
          $absolute = Url::fromUri('internal:/' . $uri, ['absolute' => TRUE])
            ->toString(TRUE)
            ->getGeneratedUrl();

          $params = Url::fromUri('internal:/' . $uri)->getRouteParameters();
          $entity_type = key($params);
          if (!empty($entity_type)) {
            $entity = \Drupal::entityTypeManager()
              ->getStorage($entity_type)
              ->load($params[$entity_type]);
            $uuid = $entity->uuid();
          }
        }
        catch (\UnexpectedValueException $e) {
          $absolute = $uri = '';
        }
      }

      $alias = \Drupal::service('path.alias_manager')->getAliasByPath("/$uri");

      $menu_tree[$order]['title'] = $menu_link->getTitle();
      $menu_tree[$order]['uri'] = $uri;
      if (isset($menuSettings['alias']) && $menuSettings['alias']) {
        $menu_tree[$order]['alias'] = ltrim($alias, '/');
      }
      if (isset($menuSettings['external']) && $menuSettings['external']) {
        $menu_tree[$order]['external'] = $external;
      }
      if (isset($menuSettings['absolute_url']) && $menuSettings['absolute_url']) {
        $menu_tree[$order]['absolute'] = $absolute;
      }
      if (isset($menuSettings['weight']) && $menuSettings['weight']) {
        $menu_tree[$order]['weight'] = $menu_link->getWeight();
      }
      if (isset($menuSettings['expanded']) && $menuSettings['expanded']) {
        $menu_tree[$order]['expanded'] = $menu_link->isExpanded();
      }
      if (isset($menuSettings['enabled']) && $menuSettings['enabled']) {
        $menu_tree[$order]['enabled'] = $menu_link->isEnabled();
      }
      if (isset($menuSettings['uuid']) && $menuSettings['uuid']) {
        $menu_tree[$order]['uuid'] = $uuid;
      }

      if (!empty($menu_item['below'])) {
        $menu_tree[$order]['below'] = [];
        $this->menuTreeDetails($menuSettings, $menu_item['below'], $menu_tree[$order]['below']);
      }
      $order++;
    }
  }

}
