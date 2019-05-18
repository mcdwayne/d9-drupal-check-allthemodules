<?php

/**
 * @file
 * Contains \Drupal\monitoring_multigraph\MultigraphListBuilder.
 */

namespace Drupal\monitoring_multigraph;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of monitoring multigraphs.
 *
 * @see \Drupal\monitoring_multigraph\Entity\Multigraph
 */
class MultigraphListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['description'] = $this->t('Description');
    $header['sensors'] = $this->t('Sensors');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\monitoring_multigraph\Entity\Multigraph $entity */
    $row['label'] = $this->getLabel($entity);
    $row['description'] = $entity->getDescription();

    // Format sensors list.
    $row['sensors'] = array();
    foreach ($entity->getSensors() as $sensor) {
      $row['sensors'][] = $sensor->label();
    }
    $row['sensors'] = implode(', ', $row['sensors']);

    return $row + parent::buildRow($entity);
  }
}
