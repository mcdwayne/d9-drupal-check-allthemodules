<?php

namespace Drupal\healthcheck\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Check entities.
 */
class CheckConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Check');
    $header['description'] = $this->t('Description');
    $header['tags'] = $this->t('Tags');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\healthcheck\Entity\CheckConfigInterface $check_config */
    $check_config = $entity;

    $row['label'] = $check_config->label();

    /** @var \Drupal\healthcheck\Plugin\HealthcheckPluginInterface $check_plugin */
    $check_plugin = $check_config->getCheck();

    $row['description'] = $check_plugin->getDescription();

    $row['tags'] = implode(', ', $check_plugin->getTags());

    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
