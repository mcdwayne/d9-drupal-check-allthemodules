<?php

namespace Drupal\x_reference\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\x_reference\Entity\XReferencedEntity;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a list controller for x_referenced_entity.
 *
 * @ingroup x_reference
 */
class XReferencedEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['entity_source'] = $this->t('Entity source');
    $header['entity_type'] = $this->t('Entity type');
    $header['entity_id'] = $this->t('Entity id');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @param XReferencedEntity $entity
   */
  public function buildRow(EntityInterface $entity) {
    $row = [
      'id' => $entity->id(),
      'entity_source' => $entity->entity_source->value,
      'entity_type' => $entity->entity_type->value,
      'entity_id' => $entity->entity_id->value,
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#prefix'] = Link::fromTextAndUrl(
      'Add X-referenced entity',
        Url::fromRoute('entity.x_referenced_entity.add_form')
    )->toString();

    return $build;
  }

}
