<?php

namespace Drupal\virtual_entities;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Virtual entity entities.
 *
 * @ingroup virtual_entities
 */
class VirtualEntityListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIds();

    return $this->storage->loadMultiple($entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Virtual entity ID');
    $header['name'] = $this->t('Name');
    $header['type'] = t('Type');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\virtual_entities\Entity\VirtualEntity */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.virtual_entity.edit_form', [
          'virtual_entity' => $entity->id(),
        ]
      )
    );
    $row['bundle']['data'] = ['#markup' => $entity->getType()];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    // Place the edit operation after the operations added by field_ui.module
    // which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      $operations['edit']['weight'] = 30;
    }

    return $operations;
  }

}
