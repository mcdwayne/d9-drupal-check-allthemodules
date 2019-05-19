<?php

namespace Drupal\worldcore\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for worldcore Payments entity.
 *
 * @ingroup worldcore
 */
class Payments extends EntityListBuilder {

  /**
   * Theming render.
   */
  public function render() {
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * Theming table builder.
   */
  public function buildHeader() {
    $header['pid'] = $this->t('ID');
    $header['created'] = $this->t('Created');
    $header['uid'] = $this->t('User');
    $header['amount'] = $this->t('Amount');
    $header['currency'] = $this->t('Currency');
    $header['memo'] = $this->t('Memo');
    $header['track'] = $this->t('WC track');
    $header['merchant_account'] = $this->t('Merchant account');
    $header['account'] = $this->t('Account');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * Theming table row builder.
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\worldcore\Entity\Currency */
    $row['pid'] = $entity->pid->value;
    $row['created'] = $entity->created->value;
    $row['uid'] = $entity->uid->value;
    $row['amount'] = $entity->amount->value;
    $row['currency'] = $entity->currency->value;
    $row['memo'] = $entity->memo->value;
    $row['track'] = $entity->track->value;
    $row['merchant_account'] = $entity->merchant_account->value;
    $row['account'] = $entity->account->value;
    $row['created'] = $entity->created->value;
    return $row + parent::buildRow($entity);
  }

}
