<?php

namespace Drupal\commerce_braintree_marketplace\Event;

use Drupal\profile\Entity\Profile;

class MerchantDisbursementExceptionEvent extends WebhookEventBase {

  /**
   * Getter for the related submerchant seller profile.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   */
  public function getProfile() {
    $pid = \Drupal::service('entity_type.manager')
      ->getStorage('profile')
      ->getQuery()
      ->condition('braintree_id.remote_id', $this->getWebhook()->merchantAccount->id)
      ->execute();
    return Profile::load(reset($pid));
  }

}
