<?php

namespace Drupal\wsdata;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Web Service Server entities.
 */
class WSServerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Web Service Server');
    $header['id'] = $this->t('Machine name');
    $header['type'] = $this->t('Connector');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $connectors = \Drupal::service('plugin.manager.wsconnector');
    $connector_definitions = $connectors->getDefinitions();
    $connector = $connector_definitions[$entity->wsconnector];

    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['type'] = $connector['label']->render();
    return $row + parent::buildRow($entity);
  }

}
