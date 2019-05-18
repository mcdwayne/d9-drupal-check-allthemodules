<?php

namespace Drupal\commerce_gocardless\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;

/**
 * Interface for the GoCardless payment gateway.
 *
 * We inherit from SupportsNotificationsInterface, even though we don't (yet!)
 * make use of the routing and controler that Commerce provides to go with it.
 * This is in anticipation of this patch to Commerce which will ensure orders
 * have the validation state: https://www.drupal.org/project/commerce/issues/2930512
 */
interface GoCardlessPaymentGatewayInterface extends OnsitePaymentGatewayInterface, SupportsNotificationsInterface {

  /**
   * Create a new GoCardless client based on this plugin's configuration.
   *
   * @return \GoCardlessPro\Client
   */
  public function createGoCardlessClient();

  /**
   * A description, shown on the GoCardless site to identify the organisation.
   *
   * @return string
   */
  public function getDescription();

  /**
   * The secret used to validate requests to the webhook controller.
   *
   * @return string
   */
  public function getWebhookSecret();

  /**
   * Try to get a nice description of a payment method by looking up bank
   * details from GoCardless.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *
   * @return string
   *   Bank account holder, bank etc, or '' if this could not be obtained.
   */
  public function getMandateDescription(PaymentMethodInterface $payment_method);

}
