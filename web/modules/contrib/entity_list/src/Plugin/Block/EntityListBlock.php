<?php

namespace Drupal\entity_list\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_list\Entity\EntityList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'EntityListBlock' block.
 *
 * @Block(
 *  id = "entity_list_block",
 *  admin_label = @Translation("Entity list block"),
 *  deriver = "Drupal\entity_list\Plugin\Derivative\EntityListBlock"
 * )
 */
class EntityListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $plugin_definition = $this->getPluginDefinition();
    $entity_list = EntityList::load($plugin_definition['entity_list_id']);
    $view_builder = $this->entityTypeManager->getViewBuilder('entity_list');
    return $view_builder->view($entity_list);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = [];
    // Adding context configuration as a cache tag to invalidate block cache
    // when the context has been modified.
    if (isset($this->configuration['context_id'])) {
      $tags[] = 'config:context.context.' . $this->configuration['context_id'];
    }
    // Add tag according to the query plugin.
    $plugin_definition = $this->getPluginDefinition();
    $entity_list = EntityList::load($plugin_definition['entity_list_id']);
    /** @var \Drupal\entity_list\Plugin\EntityListQueryInterface $query */
    $query = $entity_list->getEntityListQueryPlugin();
    $tags[] = $query->getEntityTypeId() . '_list';
    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

}
