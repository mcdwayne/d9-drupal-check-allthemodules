<?php

namespace Drupal\zuora\Soap;


/**
 * Class SubscribeRequest
 *
 * @property Account Account
 * @property Contact BillTo
 * @property PaymentMethod PaymentMethod
 * @property SubscriptionData SubscriptionData
 * @property SubscribeOptions SubscribeOptions
 */
class SubscribeRequest extends zObject {
  protected $zType = 'SubscribeRequest';

  public function setAccount(Account $account) {
    $this->Account = $account->getSoapVar();
  }

  public function setBillTo(Contact $contact) {
    $this->BillTo = $contact->getSoapVar();
  }

  public function setPaymentMethod(PaymentMethod $payment_method) {
    $this->PaymentMethod = $payment_method->getSoapVar();
  }

  public function setSubscriptionData(SubscriptionData $data) {
    $this->SubscriptionData = $data->getSoapVar();
  }

  public function setSubscribeOptions(SubscribeOptions $options) {
    $this->SubscribeOptions = $options->getSoapVar();
  }

}
