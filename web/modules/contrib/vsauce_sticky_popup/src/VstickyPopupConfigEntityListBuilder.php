<?php

namespace Drupal\vsauce_sticky_popup;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Vsauce config entity entities.
 */
class VstickyPopupConfigEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Vsauce Sticky Popup entity config');
    $header['id'] = $this->t('Machine name');
    $header['path_id'] = $this->t('Path Id');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['pathId'] = $entity->pathId();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
