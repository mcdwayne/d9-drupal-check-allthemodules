<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

namespace Drupal\commerce_multisafepay\PluginForm\StandardPayment;

use Drupal\commerce_multisafepay\API\Client;
use Drupal\commerce_multisafepay\Helpers\ApiHelper;
use Drupal\commerce_multisafepay\Helpers\GatewayHelper;
use Drupal\commerce_multisafepay\Helpers\OrderHelper;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class StandardPaymentForm extends BasePaymentOffsiteForm
{
    /**
     * @var \Drupal\commerce_multisafepay\Helpers\OrderHelper OrderHelper
     */
    protected $mspOrderHelper;
    protected $mspGatewayHelper;
    protected $mspApiHelper;
    protected $mspClient;
    protected $logStorage;

    /**
     * FormStandardHelper constructor.
     */
    public function __construct()
    {
        $this->mspOrderHelper = new OrderHelper();
        $this->mspGatewayHelper = new GatewayHelper();
        $this->mspApiHelper = new ApiHelper();
        $this->mspClient = new Client();
        $this->logStorage = \Drupal::entityTypeManager()->getStorage('commerce_log');
    }

    /**
     * Build the checkout form configuration
     *
     * {@inheritdoc}
     */
    public function buildConfigurationForm(
        array $form, FormStateInterface $form_state
    ) {
        //We will put the form builder of the Payment process of the customer here.
        parent::buildConfigurationForm($form, $form_state);

        $payment = $this->entity;

        //get order
        $order = $payment->getOrder();

        //set the mode of the gateway
        $mode = $this->mspGatewayHelper->getGatewayMode($order);

        //Create the order data
        $data = $this->mspOrderHelper->createOrderData($form, $payment);

        //set the API settings
        $this->mspApiHelper->setApiSettings($this->mspClient, $mode);

        //Post the data
        $this->mspClient->orders->post($data);

        //Place in log storage
        $this->logStorage->generate($order, 'order_payment_link')->setParams([
            'payment_link' => $this->mspClient->orders->getPaymentLink(),
        ])->save();

        //Redirect to the offsite (MSP)
        return $this->buildRedirectForm(
            $form,
            $form_state,
            $this->mspClient->orders->getPaymentLink(),
            [],
            'get'
        );

    }
}
