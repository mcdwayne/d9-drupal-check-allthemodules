<?php

namespace Drupal\worldcore\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for worldcore entity.
 *
 * @ingroup worldcore
 */
class Currencies extends EntityListBuilder {

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
    $header['currency'] = $this->t('Currency');
    $header['account'] = $this->t('WC account');
    $header['enabled'] = $this->t('Enabled');
    // $header[] = $this->t('Action');.
    return $header + parent::buildHeader();
  }

  /**
   * Theming table row builder.
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\worldcore\Entity\Currency */
    $row['currency'] = $entity->currency->value;
    $row['account'] = $entity->account->value;
    $row['enabled'] = $entity->enabled();

    return $row + parent::buildRow($entity);
  }

}
