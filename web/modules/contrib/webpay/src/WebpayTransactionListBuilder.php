<?php

namespace Drupal\webpay;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Webpay transaction entities.
 *
 * @ingroup webpay
 */
class WebpayTransactionListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['config_id'] = $this->t('Commerce');
    $header['order_number'] = $this->t('Order number');
    $header['commerce_system_id'] = $this->t('Commerce system');
    $header['amount'] = $this->t('Amount');
    $header['transaction_date'] = $this->t('Transaction Date');
    $header['response_code'] = $this->t('Response code');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();

    if (!$entity->get('config_id')->entity) {
      $row['config_id'] = $this->t('No configuration');
    }
    else {
      $config = $entity->get('config_id');
      $row['config_id'] = Link::createFromRoute(
        $config->entity->label(),
        'entity.webpay_config.canonical',
        ['webpay_config' => $config->entity->id()]
      );
    }
    $row['order_number'] = $entity->get('order_number')->value;
    $row['commerce_system_id'] = $entity->get('commerce_system_id')->value;
    $row['amount'] = $entity->get('amount')->value;
    $date_formatter = \Drupal::service('date.formatter');
    $row['transaction_date'] = $date_formatter->format($entity->get('transaction_date')->value);
    $row['response_code'] = $entity->get('response_code')->value;

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'), 'DESC');

    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = [];
    $operations['view'] = [
      'title' => $this->t('View'),
      'url' => $entity->toUrl(),
    ];

    return $operations;
  }

}
