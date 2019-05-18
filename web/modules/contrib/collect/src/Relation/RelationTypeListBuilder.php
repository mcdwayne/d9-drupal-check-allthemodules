<?php
/**
 * @file
 * Contains \Drupal\collect\Relation\RelationTypeListBuilder.
 */

namespace Drupal\collect\Relation;


use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for relation type entities.
 */
class RelationTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * The injected relation plugin manager.
   *
   * @var \Drupal\collect\Relation\RelationPluginManagerInterface
   */
  protected $relationPluginManager;

  /**
   * Constructs a new RelationTypeListBuilder object.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RelationPluginManagerInterface $relation_plugin_manager) {
    parent::__construct($entity_type, $storage);
    $this->relationPluginManager = $relation_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.collect.relation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      $this->t('Label'),
      $this->t('URI pattern'),
      $this->t('Relation plugin'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\collect\Relation\RelationTypeInterface $entity */
    return [
      $entity->label(),
      $entity->getUriPattern(),
      $this->relationPluginManager->getDefinition($entity->getPluginId())['label'],
    ] + parent::buildRow($entity);
  }


}
