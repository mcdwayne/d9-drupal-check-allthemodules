<?php

namespace Drupal\commerce_atom_payment\PluginForm\AtomRedirect;

use Drupal\commerce_atom_payment\AtomEncryption;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\user\Entity\User;

/**
 * Implements PaymentAtomForm class.
 *
 * - this class used for build to payment form.
 */
class PaymentAtomForm extends BasePaymentOffsiteForm {

  const ATOM_API_URL = 'https://payment.atomtech.in/paynetz/epi/fts';
  const ATOM_API_TEST_URL = 'https://paynetzuat.atomtech.in/paynetz/epi/fts';

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $redirect_method = 'get';
    $parameters = [];
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
    $userCurrent = \Drupal::currentUser();
    $user = User::load($userCurrent->id());
    $name = $user->getUsername();
    $date = new DrupalDateTime();
    $product_id = $payment_gateway_plugin->getConfiguration()['product_id'];
    $strReqst = "";
    $strReqst .= "login=" . $merchant_id;
    $strReqst .= "&pass=" . $access_code;
    $strReqst .= "&ttype=NBFundTransfer";
    $strReqst .= "&prodid=" . $product_id;
    $strReqst .= "&amt=" . round($payment->getAmount()->getNumber(), 2);
    $strReqst .= "&txncurr=" . $cur;
    $strReqst .= "&txnscamt=" . round($payment->getAmount()->getNumber(), 2);
    $strReqst .= "&ru=" . Url::FromRoute('commerce_payment.checkout.return', ['commerce_order' => $order_id, 'step' => 'payment'], ['absolute' => TRUE])->toString();
    $strReqst .= "&clientcode=" . $payment_gateway_plugin->getConfiguration()['client_code'];
    $strReqst .= "&txnid=" . $order_id;
    $strReqst .= "&date=" . str_replace(" ", "%20", $date->format('d/m/Y h:m:s'));
    $strReqst .= "&udf1=" . str_replace(" ", "%20", $address->getGivenName());
    $strReqst .= "&udf2=" . $order->getEmail();
    $strReqst .= "&udf3=9999999999";
    $strReqst .= "&udf4=" . str_replace(" ", "%20", $address->getAdministrativeArea());
    $strReqst .= "&custacc=" . $name . $order_id;
    $str = $merchant_id . $access_code . "NBFundTransfer" . $product_id . $order_id . round($payment->getAmount()->getNumber(), 2) . $cur;
    $encrypt = new AtomEncryption();
    $strReqst .= "&signature=" . $encrypt->signature($str, $working_key);
    if ($mode == 'test') {
      $redirect_url = self::ATOM_API_TEST_URL . "?" . $strReqst;
    }
    else {
      $redirect_url = self::ATOM_API_URL . "?" . $strReqst;
    }
    return $this->buildRedirectForm($form, $form_state, $redirect_url, $parameters, $redirect_method);
  }

}
