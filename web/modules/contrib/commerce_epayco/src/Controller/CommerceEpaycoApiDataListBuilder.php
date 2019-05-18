<?php

namespace Drupal\commerce_epayco\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of CommerceEpaycoApiData entities.
 *
 * @ingroup commerce_epayco
 */
class CommerceEpaycoApiDataListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'commerce_epayco';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['machine_name'] = $this->t('Machine Name');
    $header['info'] = $this->t('Data');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['machine_name'] = $entity->id();
    $row['info'] = [
      '#theme' => 'item_list',
      '#items' => [],
    ];
    foreach ($this->getVars($entity) as $key => $value) {
      array_push($row['info']['#items'], $key . ': ' . $value);
    }
    $row['info'] = render($row['info']);

    return $row + parent::buildRow($entity);
  }

  /**
   * Get a list of available useful variables to be show.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to fetch values from.
   */
  public function getVars(EntityInterface $entity) {
    return [
      'p_key' => $entity->getPkey(),
      'p_cust_id_cliente' => $entity->getIdClient(),
      'api_key' => $entity->getApiKey(),
      'private_key' => $entity->getPrivateKey(),
      'language' => $entity->getLanguageCode(),
      'test' => $entity->isTestMode() ? $this->t('Yes') : $this->t('No'),
    ];
  }

}
