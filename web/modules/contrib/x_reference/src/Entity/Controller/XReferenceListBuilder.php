<?php

namespace Drupal\x_reference\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\x_reference\Entity\XReference;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a list controller for x_referenced entity.
 *
 * @ingroup x_reference
 */
class XReferenceListBuilder extends EntityListBuilder {

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
    $header['type'] = $this->t('Type');
    $header['source'] = $this->t('Source');
    $header['target'] = $this->t('Target');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @param XReference $entity
   */
  public function buildRow(EntityInterface $entity) {
    $sourceEntity = $entity->getSourceEntity();
    $targetEntity = $entity->getTargetEntity();

    $row = [
      'id' => $entity->id(),
      'type' => $entity->bundle(),
      'source' => $sourceEntity ? $sourceEntity->label() : 'NULL',
      'target' => $targetEntity ? $targetEntity->label() : 'NULL',
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#prefix'] = Link::fromTextAndUrl(
      'Add X-reference',
      Url::fromRoute('x_reference.add_page')
    )->toString();

    return $build;
  }

}
