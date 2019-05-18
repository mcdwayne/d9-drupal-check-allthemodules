<?php

namespace Drupal\entity_hierarchy\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_hierarchy\Information\AncestryLabelTrait;
use Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface;
use Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory;
use Drupal\entity_hierarchy\Storage\NestedSetStorageFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for entity reference selection that includes lineage.
 *
 * @EntityReferenceSelection(
 *   id = "entity_hierarchy",
 *   label = @Translation("Entity Hierarchy"),
 *   group = "entity_hierarchy",
 *   weight = 0,
 *   deriver = "Drupal\entity_hierarchy\Plugin\Derivative\EntityHierarchySelectionDeriver"
 * )
 */
class EntityHierarchy extends DefaultSelection {

  use AncestryLabelTrait;

  /**
   * Storage factory.
   *
   * @var \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory
   */
  protected $nestedSetStorageFactory;

  /**
   * Constructs a new SelectionBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\entity_hierarchy\Storage\EntityTreeNodeMapperInterface $entityTreeNodeMapper
   *   Tree node mapper.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetNodeKeyFactory $keyFactory
   *   Node key factory.
   * @param \Drupal\entity_hierarchy\Storage\NestedSetStorageFactory $nestedSetStorageFactory
   *   Tree node storage factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityTreeNodeMapperInterface $entityTreeNodeMapper, NestedSetNodeKeyFactory $keyFactory, NestedSetStorageFactory $nestedSetStorageFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);

    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->entityTreeNodeMapper = $entityTreeNodeMapper;
    $this->keyFactory = $keyFactory;
    $this->nestedSetStorageFactory = $nestedSetStorageFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_hierarchy.entity_tree_node_mapper'),
      $container->get('entity_hierarchy.nested_set_node_factory'),
      $container->get('entity_hierarchy.nested_set_storage_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->configuration['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityManager->getStorage($target_type)->loadMultiple($result);

    // We assume target and definition are one and the same, as there is no
    // point in a hierarchy if you're referencing something else, you can't
    // go more than one level deep.
    /** @var \PNX\NestedSet\NestedSetInterface $storage */
    $storage = $this->nestedSetStorageFactory->get($this->pluginDefinition['field_name'], $target_type);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $label = $this->generateEntityLabelWithAncestry($entity, $storage, $target_type);
      $options[$bundle][$entity_id] = Html::escape($label);
    }

    return $options;
  }

}
