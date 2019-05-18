<?php

namespace Drupal\entity_pilot\ListBuilders;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder.
 */
class AccountListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    // Place the edit operation before the operations added by field_ui.module
    // which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      $operations['edit']['weight'] = 5;
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['type'] = t('Account');
    $header['carrierId'] = t('Carrier ID');
    $header['description'] = t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['type'] = $entity->link($entity->label());
    $row['carrierID'] = SafeMarkup::checkPlain($entity->getCarrierId());
    $row['description'] = Xss::filterAdmin($entity->getDescription());
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return $this->t('Entity Pilot accounts');
  }

}
