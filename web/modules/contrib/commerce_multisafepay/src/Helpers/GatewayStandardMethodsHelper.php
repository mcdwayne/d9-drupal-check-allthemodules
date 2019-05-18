<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

namespace Drupal\commerce_multisafepay\Helpers;

use Drupal\commerce_multisafepay\API\Client;
use Drupal\commerce_multisafepay\Exceptions\ExceptionHelper;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class GatewayStandardMethodsHelper extends OffsitePaymentGatewayBase implements SupportsNotificationsInterface
{
    protected $mspApiHelper;
    protected $mspGatewayHelper;
    protected $mspOrderHelper;
    protected $mspConditionHelper;
    protected $exceptionHelper;
    protected $paymentStorage;

    /**
     * GatewayStandardMethodsHelper constructor.
     * @param array $configuration
     * @param $plugin_id
     * @param $plugin_definition
     * @param EntityTypeManagerInterface $entity_type_manager
     * @param PaymentTypeManager $payment_type_manager
     * @param PaymentMethodTypeManager $payment_method_type_manager
     * @param TimeInterface $time
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
        $this->mspApiHelper = new ApiHelper();
        $this->mspGatewayHelper = new GatewayHelper();
        $this->mspOrderHelper = new OrderHelper();
        $this->mspConditionHelper = new ConditionHelper();
        $this->exceptionHelper = new ExceptionHelper();
        $this->paymentStorage = $entity_type_manager->getStorage('commerce_payment');
    }

    /**
     * Set the order in the next workflow step
     *
     * @param OrderInterface $order
     * @param $mspOrder
     *
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function nextStep(OrderInterface $order, $mspOrder)
    {
        $stateItem = $order->get('state')->first();
        $currentState = $stateItem->getValue();

        //If order is completed && Check if current state is draft
        if(OrderHelper::isStatusCompleted($mspOrder->status) && $currentState['value'] === 'draft') {

            // Place the order
            $transitions = $stateItem->getTransitions();
            $stateItem->applyTransition(current($transitions));
            $this->mspOrderHelper->logMsp($order, 'order_reopened');
            $order->save();
        }

    }

    /**
     * @param Request $request
     * @return mixed|Response
     */
    public function getMspOrder($order, $orderId){
        $client = new Client();

        //Get current gateway & Check if it is a MSP gateway
        $gateway = $order->get('payment_gateway')->first()->entity;
        if(!$this->mspGatewayHelper->isMspGateway($gateway->getPluginId())){
            return new Response("Non MSP order");
        };

        //set the mode of the gateway
        $mode = $this->mspGatewayHelper->getGatewayMode($order);

        //set the API settings
        $this->mspApiHelper->setApiSettings($client, $mode);

        return $client->orders->get('orders', $orderId);
    }

    /**
     * Set the behavior when you get a notification back form the API
     *
     * {@inheritdoc}
     */
    public function onNotify(Request $request){
        //Get the order id & check if there's no transaction id.
        $orderId = $request->get('transactionid');
        if (empty($orderId)) {
            return new Response('Error 500', 500);
        }

        //Get the order & Check if order is not null
        $order = Order::load($orderId);
        if (is_null($order)) {
            return new Response("Order does not exist");
        };

        //Get payment gateway
        $gateway = $order->get('payment_gateway')->first()->entity;

        //Get the MSP order & check if payment details has been found
        $mspOrder = $this->getMspOrder($order, $orderId);

        if (!isset($mspOrder->payment_details)) {
            return new Response("No payment details found");
        }

        //Set order in the next step
        $this->nextStep($order, $mspOrder);

        //Get the payment
        $payment = $this->createPayment($order, $mspOrder);

        //Get the MSP status & check if order has changed state
        $state = OrderHelper::getPaymentState($mspOrder->status);
        if(!is_null($state)){
            $payment->setState($state)->save();
        }

        //Check if status is uncleared
        if($mspOrder->status === OrderHelper::MSP_UNCLEARED)
        {
            $this->mspOrderHelper->logMsp($order, 'order_uncleared');
        }

        //Get the msp Gateway & Check if paid with other payment method then registered.
        $mspGateway = $mspOrder->payment_details->type;
        $this->mspGatewayHelper->logDifferentGateway($mspGateway, $gateway, $order);

        return new Response('OK');
    }

    /**
     * create and/or get payment
     *
     * @param OrderInterface $order
     * @param $mspOrder
     *
     * @return object
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function createPayment(OrderInterface $order, $mspOrder)
    {
        //Set amount
        $mspAmount = $mspOrder->amount / 100;

        //Get payment gateway
        $gateway = $order->get('payment_gateway')->first()->entity;

        //If payment already exist, else create a new payment
        if(is_null($this->paymentStorage->loadByRemoteId($mspOrder->transaction_id)))
        {
            //Check if the gateway if Banktransfer
            if($gateway->getPluginId() === 'msp_banktrans')
            {
                $this->mspOrderHelper->logMsp($order, 'order_banktransfer_started' );
            }

            $this->paymentStorage->create([
                'state'           => 'new',
                'amount'          => new Price((string)$mspAmount, $mspOrder->currency),
                'payment_gateway' => $this->entityId,
                'order_id'        => $order->id(),
                'remote_id'       => $mspOrder->transaction_id,
                'remote_state'    => $mspOrder->status,
            ])->save();

        }

        //Add payment capture to log
        if($mspOrder->status === OrderHelper::MSP_COMPLETED) {
            $this->mspOrderHelper->logMsp($order, 'order_payment_capture' );
        }

        //Save the new record
        return $this->paymentStorage->loadByRemoteId($mspOrder->transaction_id);
    }

    /**
     * Refund a payment
     *
     * @param PaymentInterface $payment
     * @param Price|null $amount
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function refundPayment(
        PaymentInterface $payment, Price $amount = null
    ) {
        //get all data
        $orderId = $payment->getOrderId();
        $currency = $amount->getCurrencyCode();

        // If not specified, refund the entire amount.
        $amount = $amount ?: $payment->getAmount();

        //Check if $payment amount is =< then refund $amount
        $this->assertRefundAmount($payment, $amount);

        //Set all data
        $data = array(
            "currency" => $currency,
            "amount" => $amount->getNumber() * 100,
            "description" => "Refund: {$orderId}"
        );

        //set the mode of the gateway
        $mode = $this->mspGatewayHelper->getGatewayMode($payment->getOrder());

        //Make API request to send refund
        $client = new Client();
        $mspApiHelper = new ApiHelper();
        $mspApiHelper->setApiSettings($client, $mode);

        $client->orders->post($data, "orders/{$orderId}/refunds");

        //If refund is processed and success is false
        if($client->orders->success === false)
        {
            $this->exceptionHelper->PaymentGatewayException("Refund declined");
        }

        //Set new refunded amount
        $oldRefundedAmount = $payment->getRefundedAmount();
        $newRefundedAmount = $oldRefundedAmount->add($amount);
        $payment->setRefundedAmount($newRefundedAmount);
        $payment->save();

        //Choose what log will be used
        if ($newRefundedAmount->lessThan($payment->getAmount())){
            $logfile = 'order_partial_refund';
        }
        else{
            $logfile = 'order_full_refund';
        }

        //place log in order
        $this->mspOrderHelper->logMsp($payment->getOrder(), $logfile);
    }
}