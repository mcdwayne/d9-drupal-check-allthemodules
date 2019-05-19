<?php

namespace Drupal\simple_access\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides Drupal\simple_access\Controller\SimpleAccessProfileListBuilder.
 */
class SimpleAccessProfileListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $weightKey = 'weight';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_access_profile_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];

    $header['label'] = $this->t('Profile');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    $row['label'] = $entity->label();

    return $row + parent::buildRow($entity);
  }

}
