<?php

namespace Drupal\commerce_ccavenue\PluginForm\CCAvenueRedirect;

use Drupal\commerce_ccavenue\CCAvenueEncryption;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\Order;

class PaymentCCAvenueForm extends BasePaymentOffsiteForm {

    const CCAVENUE_API_TEST_URL = 'https://test.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
    const CCAVENUE_API_URL = 'https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction';

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildConfigurationForm($form, $form_state);

        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;

        $redirect_method = 'post';
        /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
        $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

        $order_id = \Drupal::routeMatch()->getParameter('commerce_order')->id();
        $order = Order::load($order_id);
        $address = $order->getBillingProfile()->address->first();
        $mode = $payment_gateway_plugin->getConfiguration()['pmode'];
        $merchant_id = $payment_gateway_plugin->getConfiguration()['merchant_id'];
        $access_code = $payment_gateway_plugin->getConfiguration()['access_code'];
        $working_key = $payment_gateway_plugin->getConfiguration()['working_key'];
        $cur = $payment_gateway_plugin->getConfiguration()['currency'];
        $lng = $payment_gateway_plugin->getConfiguration()['language'];

        $parameters = [
            'merchant_id' => $merchant_id,
            'order_id' => $order_id,
            'tid' => $order_id,
            'amount' => round($payment->getAmount()->getNumber(), 2),
            'currency' => $cur,
            'language' => $lng,
            'redirect_url' => Url::FromRoute('commerce_payment.checkout.return', ['commerce_order' => $order_id, 'step' => 'payment'], array('absolute' => TRUE))->toString(),
            'cancel_url' => Url::FromRoute('commerce_payment.checkout.cancel', ['commerce_order' => $order_id, 'step' => 'payment'], ['absolute' => TRUE])->toString(),
            'billing_name' => $address->getGivenName(),
            'billing_address' => $address->getAddressLine1(),
            'billing_city' => $address->getLocality(),
            'billing_state' => $address->getAdministrativeArea(),
            'billing_country' => $address->getCountryCode(),
            'billing_zip' => $address->getPostalCode(),
            'billing_email' => $order->getEmail(),
        ];

        $merchantData = '';
        foreach ($parameters as $key => $value) {
            $merchantData .= $key . '=' . $value . '&';
        }

        //$parameters['encRequest'] = crypt($merchantData, $working_key);
        $encrypt = new CCAvenueEncryption();
        $parameters['encRequest'] = $encrypt->encrypt($merchantData, $working_key);
        $parameters['access_code'] = $access_code;

        if ($mode == 'test') {
            $redirect_url = self::CCAVENUE_API_TEST_URL;
        } else {
            $redirect_url = self::CCAVENUE_API_URL;
        }

        return $this->buildRedirectForm($form, $form_state, $redirect_url, $parameters, $redirect_method);
    }

}
