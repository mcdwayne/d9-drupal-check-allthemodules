<?php

/**
 * @file
 * Contains \Drupal\efq_views\EqViewsData.
 */

namespace Drupal\efq_views;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\EntityViewsDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EqViewsData implements EntityViewsDataInterface, EntityHandlerInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  public function __construct(EntityTypeInterface $entityType) {
    $this->entityType = $entityType;
  }


  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = [];
    $base_table = 'eq__' . $this->entityType->id();

    $data[$base_table]['table']['group'] = $this->t('EQ @label', ['@label' => $this->entityType->getLabel()]);
    $data[$base_table]['table']['provider'] = $this->entityType->getProvider();

    $data[$base_table]['table']['base'] = [
      'query_id' => 'entity_field_query',
      'field' => $this->entityType->getKey('id'),
      'title' => $this->t('EQ @label', ['@label' => $this->entityType->getLabel()]),
      'cache_contexts' => $this->entityType->getListCacheContexts(),
    ];

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsTableForEntityType(EntityTypeInterface $entity_type) {
    return 'efq__' . $entity_type->id();
  }

}
