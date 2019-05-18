<?php

namespace Drupal\commerce_braintree_marketplace\Event;

use Braintree\WebhookNotification;
use Drupal\profile\Entity\ProfileInterface;

class MerchantEvent extends WebhookEventBase {

  /**
   * The merchant account.
   *
   * @var \Braintree\MerchantAccount
   */
  protected $merchantAccount;

  /**
   * Merchant profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $profile;

  /**
   * MerchantEvent constructor.
   *
   * @param \Braintree\MerchantAccount $account
   * @param \Braintree\MerchantAccount $master
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   */
  public function __construct(WebhookNotification $result, ProfileInterface $profile) {
    parent::__construct($result);
    $this->merchantAccount = $result->merchantAccount;
    $this->profile = $profile;
  }

  /**
   * Getter for the merchant account.
   *
   * @return \Braintree\MerchantAccount
   */
  public function getMerchantAccount() {
    return $this->merchantAccount;
  }

  /**
   * Getter for the master merchant account.
   *
   * @return \Braintree\MerchantAccount
   */
  public function getMasterMerchantAccount() {
    return $this->merchantAccount->masterMerchantAccount;
  }

  /**
   * Getter for the mrechant profile.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   */
  public function getProfile() {
    return $this->profile;
  }

}
