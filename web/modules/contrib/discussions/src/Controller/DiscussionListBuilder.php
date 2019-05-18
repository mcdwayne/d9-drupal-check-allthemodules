<?php

namespace Drupal\discussions\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list of Discussions.
 */
class DiscussionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['type'] = $this->t('Type');
    $header['subject'] = $this->t('Subject');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['type'] = $entity->bundle();
    $row['subject'] = $entity->subject->value;

    return $row + parent::buildRow($entity);
  }

}
