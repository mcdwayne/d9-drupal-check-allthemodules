<?php

namespace Drupal\oh_regular;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for Oh Map entities.
 */
class OhRegularMapListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['type'] = $this->t('Mapping');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\oh_regular\OhRegularMapInterface $entity */
    $row['type'] = $entity->toLink(NULL, 'edit-form');
    return $row + parent::buildRow($entity);
  }

}
