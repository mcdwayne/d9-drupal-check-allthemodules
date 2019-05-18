<?php

namespace Drupal\dcat_import;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of DCAT source entities.
 */
class DcatSourceListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('DCAT source');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    $operations['log'] = [
      'title' => t('Import log'),
      'weight' => -20,
      'url' => Url::fromRoute('dcat_import.log', ['dcat_source' => $entity->id()]),
    ];

    $operations['import'] = [
      'title' => t('Import'),
      'weight' => -10,
      'url' => Url::fromRoute('dcat_import.import', ['dcat_source' => $entity->id()]),
    ];

    return $operations;
  }

}
