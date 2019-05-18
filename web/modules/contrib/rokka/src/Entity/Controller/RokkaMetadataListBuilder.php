<?php

namespace Drupal\rokka\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Provides a list controller for rokka_metadata entity.
 *
 */
class RokkaMetadataListBuilder extends EntityListBuilder {

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
    $header['name'] = $this->t('Name');
    $header['hash'] = $this->t('Hash');
    $header['filesize'] = $this->t('File size');
    $header['uri'] = $this->t('Uri');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\rokka\Entity\RokkaMetadata */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.rokka_metadata.edit_form',
      ['rokka_metadata' => $entity->id()]
    );
    $row['hash'] = $entity->getHash();
    $row['filesize'] = $entity->getFilesize();
    $row['uri'] = $entity->getUri();
    return $row + parent::buildRow($entity);
  }

}
