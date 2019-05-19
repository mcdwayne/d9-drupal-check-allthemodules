<?php

namespace Drupal\spectra_connect\Entity\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Spectra Connect entities.
 */
class SpectraConnectListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Connector Name');
    $header['id'] = $this->t('Machine Name');
    $header['plugin'] = $this->t('Spectra Plugin');
    $header['api_key'] = $this->t('API Key');
    $header['delete_endpoint'] = $this->t('DELETE Endpoint');
    $header['get_endpoint'] = $this->t('GET Endpoint');
    $header['post_endpoint'] = $this->t('POST Endpoint');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['plugin'] = isset($entity->plugin) ? $entity->plugin : '';
    $row['api_key'] = isset($entity->api_key) ? $entity->api_key : '';
    $row['delete_endpoint'] = isset($entity->delete_endpoint) ? $entity->delete_endpoint : '';
    $row['get_endpoint'] = isset($entity->get_endpoint) ? $entity->get_endpoint : '';
    $row['post_endpoint'] = isset($entity->post_endpoint) ? $entity->post_endpoint : '';

    return $row + parent::buildRow($entity);
  }

}
