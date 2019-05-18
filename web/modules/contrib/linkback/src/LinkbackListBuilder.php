<?php

namespace Drupal\linkback;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of linkback entities.
 *
 * @see \Drupal\linkback\Entity\Linkback
 */
class LinkbackListBuilder extends EntityListBuilder {

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
    $header['title'] = $this->t('Title');
    $header['excerpt'] = $this->t('Excerpt');
    $header['origin'] = $this->t('Origin');
    $header['handler'] = $this->t('Handler');
    $header['ref_content'] = $this->t('Local Content');
    $header['remote_url'] = $this->t('Remote content');
    $header['date'] = $this->t('Creation date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['name'] = $entity->link();
    $row['excerpt'] = $entity->getExcerpt();
    $row['origin'] = $entity->getOrigin();
    $row['handler'] = $entity->getHandler();
    $row['ref_content'] = $entity->ref_content->referencedEntities()[0]->link();
    $row['remote_url'] = $entity->getUrl();
    $row['date'] = $entity->getCreatedTime();
    return $row + parent::buildRow($entity);
  }

}
