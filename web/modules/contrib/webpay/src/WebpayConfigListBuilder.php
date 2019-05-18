<?php

namespace Drupal\webpay;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Webpay config entities.
 */
class WebpayConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Configuration Name');
    $header['commerce_code'] = $this->t('Commerce Code');
    $header['environment'] = $this->t('Environment');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $entity->label();
    $row['commerce_code'] = $entity->get('commerce_code');
    $row['environment'] = $entity->getEnvironment();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = [];
    $operations['test'] = [
      'title' => $this->t('Test'),
      'url' => $entity->toUrl('test'),
    ];
    $operations['logs'] = [
      'title' => $this->t('Logs'),
      'url' => $entity->toUrl('logs'),
    ];

    return $operations + parent::getDefaultOperations($entity);
  }
}
