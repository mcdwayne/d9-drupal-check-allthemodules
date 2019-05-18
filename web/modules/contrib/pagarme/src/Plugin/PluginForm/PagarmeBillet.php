<?php
namespace Drupal\pagarme\Plugin\PluginForm;

use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\Price;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pagarme\Helpers\PagarmeUtility;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PagarmeBillet extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $order = $payment->getOrder();
    $pagarmeDrupal = new \Drupal\pagarme\Pagarme\PagarmeDrupal();
    $plugin_config = $payment_gateway_plugin->getConfiguration();
    $pagarmeDrupal->setPluginConfiguration($plugin_config);
    $pagarmeDrupal->setOrder($order);
    $currency_code = $payment->getAmount()->getCurrencyCode();
    $amount = $pagarmeDrupal->calculateBoletoAmount();

    $form['pagarme_payment_billet'] = [
      '#type' => 'details',
      '#title' => t('Payment with billet'),
      '#open' => TRUE
    ];
    $form['pagarme_payment_billet']['custom_billet_message'] = [
      '#type' => 'markup',
      '#markup' => '<div id="pagarme-custom-billet-message">'.$plugin_config['pagarme_boleto_helper_text'].'</div>',
    ];
    $form['pagarme_payment_billet']['custom_billet_amount'] = [
      '#type' => 'markup',
      '#markup' => '<div id="pagarme-custom-billet-amount">Total: ' . PagarmeUtility::currencyAmountFormat($amount, $currency_code, 'integer') . '</div>',
    ];
    $form['pagarme_payment_billet']['custom_billet_amount_discounted'] = [
      '#type' => 'hidden',
      '#value' => $amount,
    ];
    $form['pagarme_payment_billet']['answer'] = [
      '#type' => 'hidden',
      '#value' => t('boleto'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if (!empty($values['pagarme_payment_billet']['custom_billet_amount_discounted'])) {
      $amount = $values['pagarme_payment_billet']['custom_billet_amount_discounted'];
      $pagarme_answer = $values['pagarme_payment_billet']['answer'];
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = $this->entity;
      /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
      $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
      $plugin_config = $payment_gateway_plugin->getConfiguration();
      $pagarmeDrupal = new \Drupal\pagarme\Pagarme\PagarmeDrupal($plugin_config['pagarme_api_key']);
      $pagarmeDrupal->setPluginConfiguration($plugin_config);
      $order = $payment->getOrder();
      $order->setData('pagarme_payment_method', $pagarme_answer);
      $pagarmeDrupal->setOrder($order);
      if ($plugin_config['pagarme_debug']) {
        \Drupal::logger('pagarme')->debug(t('@payment_way: <pre>@pre</pre>'), array('@pre' => print_r($pagarme_answer, TRUE)));
      }
      //Dados do cliente
      $customer = [];
      $customer['address'] = [];
      $customer['phone'] = [];
      $token = \Drupal::token();
      if (!empty($plugin_config['pagarme_street_number_token'])) {
        $customer['address']['street_number'] = $token->replace($plugin_config['pagarme_street_number_token'], ['commerce_order' => $order]);
      }

      if (!empty($plugin_config['pagarme_cpf_token'])) {
        $customer['document_number'] = $token->replace($plugin_config['pagarme_cpf_token'], ['commerce_order' => $order]);
      } else if (!empty($plugin_config['pagarme_cnpj_token'])) {
        $customer['document_number'] = $token->replace($plugin_config['pagarme_cnpj_token'], ['commerce_order' => $order]);
      }

      if (!empty($plugin_config['pagarme_phone_ddd_token'])) {
        $customer['phone']['ddd'] = $token->replace($plugin_config['pagarme_phone_ddd_token'], ['commerce_order' => $order]);
      }

      if (!empty($plugin_config['pagarme_phone_number_token'])) {
        $customer['phone']['number'] = $token->replace($plugin_config['pagarme_phone_number_token'], ['commerce_order' => $order]);
      }

      if (!empty($plugin_config['pagarme_gender_token'])) {
        $customer['gender'] = $token->replace($plugin_config['pagarme_gender_token'], ['commerce_order' => $order]);
      }
      //TODO bug date field bug
      // if (!empty($plugin_config['pagarme_birthday_token'])) {
      //   $customer_born_at = $token->replace($plugin_config['pagarme_birthday_token'], array('commerce_order' => $order));
      //   $customer['born_at'] = date('m-d-Y', strtotime($customer_born_at));
      // }
      \Drupal::moduleHandler()->alter('pagarme_customer', $customer, $order);
      $pagarmeDrupal->setCustomer($customer);

      try {
        $transaction = $pagarmeDrupal->boletoTransaction($amount);
        $order->setData('pagarme_payment_boleto_url', $transaction->getBoletoUrl());
        $order->setData('pagarme_payment_transaction_id', $transaction->getId());
        $order->setData('pagarme_payment_config', $plugin_config);
        $order->save();
        $payment_gateway_plugin->createPayment($transaction, $order, $payment);
      } catch (\Exception $e) {
        \Drupal::logger('pagarme')->error($e->getMessage());
        drupal_set_message(t('There was an error with Pagar.me. Please try again later.'), 'error');
        $redirect_url = 'internal:/' . $token->replace($plugin_config['pagarme_checkout_failure_url'], array('commerce_order' => $order));
        $redirect_url = Url::fromUri($redirect_url, array('absolute' => TRUE))->toString();
        $response = new RedirectResponse($redirect_url);
        $response->send();
        exit;
      }
    }
  }
}
