<?php

namespace Drupal\media_reference_revisions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Media reference revision entities.
 *
 * @ingroup media_reference_revisions
 */
class MediaReferenceRevisionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Media reference revision ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\media_reference_revisions\Entity\MediaReferenceRevision */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.media_reference_revision.edit_form',
      ['media_reference_revision' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
