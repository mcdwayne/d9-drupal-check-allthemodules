<?php

/**
 * @file
 * Contains \Drupal\block_node_type_negate\Plugin\Condition.
 */

namespace Drupal\block_node_type_negate\Plugin\Condition;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Plugin\Condition\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Node Bundle Negate' condition.
 *
 * @Condition(
 *   id = "node_type_negate",
 *   label = @Translation("Node Bundle Negate"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"), required = FALSE)
 *   }
 * )
 *
 */
class NodeTypeNegate extends NodeType {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Creates a new NodeTypeNegate instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param array $configuration
   *   The plugin configuration, that is an array with configuration values
   *   keyed by configuration option name. The special key 'context' may be
   *   used to initialize the defined contexts by setting it to an array of
   *   context values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EntityStorageInterface $entity_storage, array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($entity_storage, $configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('node_type'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['bundles']) > 1) {
      $bundles = $this->configuration['bundles'];
      $last = array_pop($bundles);
      $bundles = implode(', ', $bundles);
      return $this->t('The node bundle is not @bundles or @last', array('@bundles' => $bundles, '@last' => $last));
    }
    $bundle = reset($this->configuration['bundles']);
    return $this->t('The node bundle is not @bundle', array('@bundle' => $bundle));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $route_name = $this->routeMatch->getRouteName();
    $node = $this->getContextValue('node');
    if ($route_name != 'entity.node.canonical' || !$node || (empty($this->configuration['bundles']) && !$this->isNegated())) {
      return TRUE;
    }
    return empty($this->configuration['bundles'][$node->getType()]);
  }

}
