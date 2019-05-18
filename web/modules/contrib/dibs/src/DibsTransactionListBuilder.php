<?php

namespace Drupal\dibs;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Dibs transaction entities.
 *
 * @ingroup dibs
 */
class DibsTransactionListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['hash'] = $this->t('Dibs transaction HASH');
    $header['order_id'] = $this->t('Order ID');
    $header['amount'] = $this->t('Amount');
    $header['currency'] = $this->t('Currency');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\dibs\Entity\DibsTransaction */
    $row['hash'] = $entity->hash->value;
    $row['order_id'] = $this->l(
      $entity->order_id->value,
      new Url(
        'entity.dibs_transaction.edit_form', array(
          'dibs_transaction' => $entity->id(),
        )
      )
    );
    $row['amount'] = $entity->amount->value;
    $row['currency'] = $entity->currency->value;
    $row['created'] = $entity->created->value;
    return $row + parent::buildRow($entity);
  }

}
