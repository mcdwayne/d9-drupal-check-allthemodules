<?php

namespace Drupal\commerce_opp\Plugin\Commerce\PaymentGateway;

/**
 * Provides the Open Payment Platform COPYandPAY gateway for virtual accounts.
 *
 * @CommercePaymentGateway(
 *   id = "opp_copyandpay_virtual",
 *   label = "Open Payment Platform COPYandPAY (virtual accounts)",
 *   display_label = "Virtual account",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_opp\PluginForm\CopyAndPayForm",
 *   },
 *   payment_method_types = {"paypal"},
 * )
 */
class CopyAndPayVirtualAccount extends CopyAndPayBase {

  /**
   * {@inheritdoc}
   */
  protected function getBrandOptions() {
    return $this->brandRepository->getVirtualAccountBrandLabels();
  }

}
