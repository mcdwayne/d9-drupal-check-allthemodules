<?php

namespace Drupal\payex;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

class PayExSettingListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['live'] = $this->t('live');
    $header['merchant_account'] = $this->t('Merchant account');
    $header['ppg'] = $this->t('PPG');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $entity->label();
    $row['live'] = $entity->isLive() ? '✓' : '✖';
    $row['merchant_account'] = $entity->getMerchantAccount();
    $row['ppg'] = $entity->getPPG();
    return $row + parent::buildRow($entity);
  }

}
