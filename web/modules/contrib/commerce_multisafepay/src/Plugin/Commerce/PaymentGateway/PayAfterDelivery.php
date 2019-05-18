<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

namespace Drupal\commerce_multisafepay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multisafepay\Helpers\GatewayStandardMethodsHelper;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Off-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "msp_payafterdelivery",
 *   label = "MultiSafepay (Pay After Delivery)",
 *   display_label = "Pay After Delivery",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_multisafepay\PluginForm\StandardPayment\StandardPaymentForm",
 *   },
 * )
 */

class PayAfterDelivery extends GatewayStandardMethodsHelper
{
    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        //Make the Condition
        $orderTotalCondition = $this->mspConditionHelper->orderTotalCondition('<=', '300', 'EUR');
        $this->mspConditionHelper->orderCurrencyCondition('Euro');

        //Set the values
        $form_state->setValues(array_merge($orderTotalCondition, $form_state->getValues()));

        //build default form
        $form = parent::buildConfigurationForm($form, $form_state);

        // Make a message
        $form['details'] = $this->mspConditionHelper->orderConditionMessage();

        return $form;
    }
}