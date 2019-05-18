<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */


namespace Drupal\commerce_multisafepay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multisafepay\Helpers\GatewayStandardMethodsHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the Off-Site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "msp_ideal",
 *   label = "MultiSafepay (iDEAL)",
 *   display_label = "iDEAL",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_multisafepay\PluginForm\Ideal\IdealForm",
 *   },
 * )
 */

class Ideal extends GatewayStandardMethodsHelper implements SupportsRefundsInterface
{
    /**
     * Build the unique iDeal configuration form
     *
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        //Make the Condition
        $this->mspConditionHelper->orderCurrencyCondition('Euro');

        //build default form
        $form = parent::buildConfigurationForm($form, $form_state);

        // Make a message
        $form['details'] = $this->mspConditionHelper->orderConditionMessage();

        return $form;
    }
}