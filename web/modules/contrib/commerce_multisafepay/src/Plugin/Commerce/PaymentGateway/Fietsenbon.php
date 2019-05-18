<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */


namespace Drupal\commerce_multisafepay\Plugin\Commerce\PaymentGateway;


use Drupal\commerce_multisafepay\Helpers\GatewayStandardMethodsHelper;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the Off-Site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "msp_fietsenbon",
 *   label = "MultiSafepay (Fietsenbon)",
 *   display_label = "Fietsenbon",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_multisafepay\PluginForm\StandardPayment\StandardPaymentForm",
 *   },
 * )
 */

class Fietsenbon extends GatewayStandardMethodsHelper implements SupportsRefundsInterface
{
}