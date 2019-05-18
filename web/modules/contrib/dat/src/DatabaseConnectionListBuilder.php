<?php

namespace Drupal\dat;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\dat\Entity\DatabaseConnection;

/**
 * Provides a listing of Database connection entities.
 */
class DatabaseConnectionListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Database connection');
    $header['id'] = $this->t('Machine name');
    $header['driver'] = $this->t('Driver');
    $header['host'] = $this->t('Host');
    $header['username'] = $this->t('Username');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\dat\Entity\DatabaseConnectionInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['driver'] = DatabaseConnection::getOptions('driver')[$entity->get('driver')];
    $row['host'] = $entity->get('host');
    $row['username'] = $entity->get('username');
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $i = 0;
    $operation_data = [
      'adminer' => $this->t('Adminer'),
      'editor' => $this->t('Editor'),
      'clone' => $this->t('Clone'),
    ];
    foreach ($operation_data as $operation => $title) {
      if ($entity->access($operation) && $entity->hasLinkTemplate($operation)) {
        $operations[$operation] = [
          'title' => $title,
          'weight' => $i,
          'url' => $entity->toUrl($operation),
        ];
      }
      $i++;
    }

    return $operations;
  }

}
