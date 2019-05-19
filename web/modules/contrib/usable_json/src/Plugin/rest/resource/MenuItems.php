<?php
/**
 * @file
 * Create the menu item REST resource.
 */

namespace Drupal\usable_json\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a resource to get bundles by entity.
 *
 * @RestResource(
 *   id = "menu_items",
 *   label = @Translation("Menu items per menu"),
 *   uri_paths = {
 *     "canonical" = "/menu_items/{menu_name}"
 *   }
 * )
 */
class MenuItems extends ResourceBase {

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
   * A instance of the alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * A list of menu items.
   *
   * @var array
   */
  protected $menuItems = array();

  /**
   * The maximum depth we want to return the tree.
   *
   * @var int
   */
  protected $maxDepth = 0;

  /**
   * The minimum depth we want to return the tree from.
   *
   * @var int
   */
  protected $minDepth = 1;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityManagerInterface $entity_manager,
    AccountProxyInterface $current_user,
    AliasManagerInterface $alias_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
    $this->aliasManager = $alias_manager;
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
      $container->get('current_user'),
      $container->get('path.alias_manager')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of menu items for specified menu name.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing a list of bundle names.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   A HTTP Exception.
   */
  public function get($menu_name = NULL) {
    if ($menu_name) {
      // Setup variables.
      $this->setup();

      $menu_tree = \Drupal::menuTree();

      // Set the parameters.
      $parameters = new MenuTreeParameters();
      $parameters->onlyEnabledLinks();

      if (!empty($this->maxDepth)) {
        $parameters->setMaxDepth($this->maxDepth);
      }

      $parameters->setMinDepth($this->minDepth);

      // Load the tree based on this set of parameters.
      $tree = $menu_tree->load($menu_name, $parameters);
      // Transform the tree using the manipulators you want.
      $manipulators = array(
        // Only show links that are accessible for the current user.
        array('callable' => 'menu.default_tree_manipulators:checkAccess'),
        // Use the default sorting of menu links.
        array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
      );
      $tree = $menu_tree->transform($tree, $manipulators);

      // Finally, build a renderable array from the transformed tree.
      $menu = $menu_tree->build($tree);

      if (isset($menu['#items'])) {
        $this->getMenuItems($menu['#items'], $this->menuItems);

        if (!empty($this->menuItems)) {
          $cacheMetadata = CacheableMetadata::createFromRenderArray($menu);
          $resource = new ResourceResponse(array_values($this->menuItems));

          return $resource->addCacheableDependency($cacheMetadata);
        }
      }

      $resource = new ResourceResponse([]);
      $resource->getCacheableMetadata()
        ->addCacheTags(['config:system.menu:' . $menu_name]);
      return $resource;
    }
    throw new HttpException(t("Menu name was not provided"));
  }

  /**
   * Generate the menu tree we can use in JSON.
   *
   * @param array $tree
   *   The menu tree.
   * @param array $items
   *   The already created items.
   */
  private function getMenuItems(array $tree, array &$items = array()) {
    foreach ($tree as $item_value) {
      /* @var $org_link \Drupal\Core\Menu\MenuLinkDefault */
      $org_link = $item_value['original_link'];
      $item_name = $org_link->getDerivativeId();
      if (empty($item_name)) {
        $item_name = $org_link->getBaseId();
      }

      /* @var $url \Drupal\Core\Url */
      $url = $item_value['url'];

      $external = FALSE;
      if ($url->isExternal()) {
        $uri = $url->getUri();
        $external = TRUE;
      }
      else {
        $uri = $url->getInternalPath();
      }

      $alias = $this->aliasManager->getAliasByPath("/" . $uri);

      $items[$item_name] = array(
        'key' => $item_name,
        'title' => $org_link->getTitle(),
        'uri' => $uri,
        'alias' => $alias,
        'external' => $external,
      );

      if (!empty($item_value['below'])) {
        $items[$item_name]['below'] = array();
        $this->getMenuItems($item_value['below'], $items[$item_name]['below']);
      }
    }
  }

  /**
   * This function is used to generate some variables we need to use.
   *
   * These variables are available in the url.
   */
  private function setup() {
    // Get the current request.
    $request = \Drupal::request();

    // Get and set the max depth if available.
    $max = $request->get('max_depth');
    if (!empty($max)) {
      $this->maxDepth = $max;
    }

    // Get and set the min depth if available.
    $min = $request->get('min_depth');
    if (!empty($min)) {
      $this->minDepth = $min;
    }
  }

}
