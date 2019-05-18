<?php

namespace Drupal\webpay\Entity;

use Drupal\Core\Entity\EntityViewBuilder;


/**
 * View builder handler for webpay transaction.
 */
class WebpayTransactionViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\node\NodeInterface[] $entities */
    if (empty($entities)) {
      return;
    }

    foreach ($entities as $id => $entity) {

      $build[$id]['voucher'] = [
        '#theme' => 'webpay_voucher',
        '#transaction' => $entity,
      ];
    }
  }
}
