<?php

namespace Drupal\commercepayu\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the PayU payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payu_payment",
 *   label = @Translation("PayU Payment"),
 *   display_label = @Translation("PayU"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commercepayu\PluginForm\PayuCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class PayuCheckout extends OffsitePaymentGatewayBase {

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildConfigurationForm($form, $form_state);
        dpm($form);

        $form['merchant_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Merchant Id'),
            '#default_value' => $this->configuration['merchant_id'],
            '#required' => TRUE,
        ];
        $form['merchant_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Merchant Key'),
            '#default_value' => $this->configuration['merchant_key'],
            '#required' => TRUE,
        ];
        $form['salt'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Salt'),
            '#default_value' => $this->configuration['salt'],
            '#required' => TRUE,
        ];


        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
        parent::submitConfigurationForm($form, $form_state);
        if (!$form_state->getErrors()) {
            $values = $form_state->getValue($form['#parents']);
            $this->configuration['merchant_id'] = $values['merchant_id'];
            $this->configuration['merchant_key'] = $values['merchant_key'];
            $this->configuration['salt'] = $values['salt'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onReturn(OrderInterface $order, Request $request) {
		    $status = $request->get('status');
	      if($status == 'success') {
           drupal_set_message($this->t('Your payment was successful with Order id : @orderid', ['@orderid' => $order->id()]));
			      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
			      $payment = $payment_storage->create([
					      'state' => 'authorization',
					      'amount' => $order->getTotalPrice(),
					      'payment_gateway' => $this->entityId,
					      'order_id' => $order->id(),
					      'test' => $this->getMode() == 'test',
					      'remote_id' => $order->id(),
					      'remote_state' => $status,
					      'authorized' => REQUEST_TIME,
			      ]);
			      $payment->save();
         }
         else {
	      		drupal_set_message($this->t('Transaction Failed'), 'error');
         }
    }
}