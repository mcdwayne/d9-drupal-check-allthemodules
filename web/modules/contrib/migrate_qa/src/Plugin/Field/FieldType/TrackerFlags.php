<?php

namespace Drupal\migrate_qa\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Computed field listing flags that reference this tracker.
 */
class TrackerFlags extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $id = $this->getParent()->getValue()->id();

    $query = $this->entityTypeManager
      ->getStorage('migrate_qa_flag')
      ->getQuery();

    $flag_ids = $query
      ->condition('tracker', $id, '=')
      ->sort('field', 'ASC')
      // @todo Sort by term name instead of tid.
      ->sort('flag_type', 'ASC')
      ->execute();

    foreach ($flag_ids as $delta => $flag_id) {
      $this->list[$delta] = $this->createItem($delta, $flag_id);
    }

  }

}
