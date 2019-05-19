<?php

/**
 * @file
 * Contains \Drupal\wisski_bulkedit\TableListBuilder.
 */
   
namespace Drupal\wisski_bulkedit;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

class TableListBuilder extends ConfigEntityListBuilder {
  
  protected $dateFormatter = NULL;

  
  protected function dateFormatter() {
    if (!$this->dateFormatter) {
      $this->dateFormatter = \Drupal::service('date.formatter');
    }
    return $this->dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['table_name'] = $this->t('Table name');
    $header['size'] = $this->t('Cols x Rows');
    $header['timestamp'] = $this->t('From');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity) {
    $rc = $entity->countRows();
    $cc = $entity->countColumns();
    $row = [
      'label' => $entity->label(),
      'table_name' => $entity->tableName(),
      'size' => $this->t($rc === NULL || $cc === NULL ? 'N/A' : '@c x @r', ['@r' => $rc, '@c' => $cc]),
      'timestamp' => $this->dateFormatter()->format($entity->timestamp() ?: 0),
    ];
    return $row + parent::buildRow($entity);
  }

}
