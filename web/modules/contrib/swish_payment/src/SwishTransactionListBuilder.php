<?php

namespace Drupal\swish_payment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Swish transaction entities.
 *
 * @ingroup swish_payment
 */
class SwishTransactionListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Swish transaction ID');
    $header['transaction_id'] = $this->t('Transaction Id');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\swish_payment\Entity\SwishTransaction */
    $row['id'] = $entity->id();
    $row['transaction_id'] = $this->l(
      $entity->label(),
      new Url(
        'entity.swish_transaction.edit_form', array(
          'swish_transaction' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
