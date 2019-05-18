<?php

namespace Drupal\bibcite_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Keyword entities.
 *
 * @ingroup bibcite_entity
 */
class KeywordListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\bibcite_entity\Entity\Keyword */
    $row['name'] = Link::createFromRoute($entity->label(), 'entity.bibcite_keyword.canonical', [
      'bibcite_keyword' => $entity->id(),
    ]);
    return $row + parent::buildRow($entity);
  }

}
