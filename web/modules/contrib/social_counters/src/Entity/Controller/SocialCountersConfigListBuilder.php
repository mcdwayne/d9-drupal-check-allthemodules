<?php

/**
 * @file
 * Contains \Drupal\social_counters\Entity\Controller\SocialCountersConfigListBuilder.
 */
namespace Drupal\social_counters\Entity\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for social_counters_entity.
 */
class SocialCountersConfigListBuilder extends EntityListBuilder {
  /**
   * Social Counters manager.
   */
  protected $social_counters_manager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.social_counters')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, PluginManagerInterface $social_counters_manager) {
    parent::__construct($entity_type, $storage);
    $this->social_counters_manager = $social_counters_manager;
  }

   /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'name' => $this->t('Name'),
      'id' => $this->t('Id'),
      'plugin' => $this->t('Plugin')
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $plugin_definitions = $this->social_counters_manager->getDefinitions();

    $row = array(
      'name' => $entity->label(),
      'id' => $entity->id(),
      'plugin' => !empty($plugin_definitions[$entity->plugin_id]) ? $plugin_definitions[$entity->plugin_id]['label']->render() : '',
    );

    return $row + parent::buildRow($entity);
  }
}
