<?php

namespace Drupal\commerce_opp\Plugin\Commerce\PaymentGateway;

/**
 * Provides the Open Payment Platform COPYandPAY gateway for credit cards.
 *
 * @CommercePaymentGateway(
 *   id = "opp_copyandpay_card",
 *   label = "Open Payment Platform COPYandPAY (credit cards)",
 *   display_label = "Credit card",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_opp\PluginForm\CopyAndPayForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex",
 *     "dinersclub",
 *     "discover",
 *     "jcb",
 *     "maestro",
 *     "mastercard",
 *     "unionpay",
 *     "visa",
 *   },
 * )
 */
class CopyAndPayCardAccount extends CopyAndPayBase {

  /**
   * {@inheritdoc}
   */
  protected function allowMultipleBrands() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBrandOptions() {
    return $this->brandRepository->getCardAccountBrandLabels();
  }

}
