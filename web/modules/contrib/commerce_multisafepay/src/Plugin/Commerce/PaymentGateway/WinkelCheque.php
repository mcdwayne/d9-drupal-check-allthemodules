<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */


namespace Drupal\commerce_multisafepay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multisafepay\Helpers\GatewayStandardMethodsHelper;

/**
 * Provides the Off-Site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "msp_winkelcheque",
 *   label = "MultiSafepay (Winkel Cheque)",
 *   display_label = "Winkel Cheque",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_multisafepay\PluginForm\StandardPayment\StandardPaymentForm",
 *   },
 * )
 */

class WinkelCheque extends GatewayStandardMethodsHelper
{
}
