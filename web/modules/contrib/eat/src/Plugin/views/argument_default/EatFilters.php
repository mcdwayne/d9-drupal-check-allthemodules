<?php

namespace Drupal\eat\Plugin\views\argument_default;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;


/**
 * Default argument plugin to extract entity reference content id from context node.
 *
 * @ViewsArgumentDefault(
 *   id = "eat",
 *   title = @Translation("Content ID from path for EAT")
 * )
 */
class EatFilters extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs a new instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    if (($node = $this->routeMatch->getParameter('node')) && $node instanceof NodeInterface) {
      $nid = $node->id();
      // Get taxonomy id of the node
      $tid = eat_load_all_for_entity($nid);
      // Return taxonomy id if set
      if ($tid) {
        return $tid[0]->tid;
      }
      else {
        // If we have a parent then run a check.
        $thisPath = \Drupal::request()->getRequestUri();
        $parent_path = explode("/", $thisPath);
        $path = \Drupal::service('path.alias_manager')->getPathByAlias('/'.$parent_path[1]);
        $parent_node_fragment = explode("/", $path);
        $parent_nid = $parent_node_fragment[2];
        $tid = eat_load_all_for_entity($parent_nid);
        if ($tid) {
          return $tid[0]->tid;
        }
      }
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

}
