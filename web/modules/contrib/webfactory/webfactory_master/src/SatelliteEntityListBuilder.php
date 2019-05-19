<?php

namespace Drupal\webfactory_master;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Satellite entity entities.
 */
class SatelliteEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Satellite entity');
    $header['id'] = $this->t('Machine name');
    $header['profile'] = $this->t('Profile');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['profile'] = $entity->get('profile');
    if ($entity->isDeployed()) {
      $row['status']['data'] = [
        '#markup' =>
        '<a href="http://' . $entity->get('host') . '" target="_blank">' . 'http://' . $entity->get('host') . '</a>',
      ];
    }
    elseif ($entity->isPending()) {
      $row['status']['data'] = [
        '#markup' => '<div class="sat-status-deploy" data-satId="' . $entity->id() . '"></div>',
      ];
    }
    else {
      $row['status'] = $this->t('Not deployed');
    }

    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = array();
    if ($entity->access('deploy') && $entity->hasLinkTemplate('deploy-form')) {
      $operations['deploy'] = array(
        'title' => $this->t('Deploy'),
        'weight' => 50,
        'url' => $entity->urlInfo('deploy-form'),
      );
    }

    $operations += parent::getOperations($entity);
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#attached'] = [
      'library' => ['webfactory_master/admin'],
    ];

    return $build;
  }

}
