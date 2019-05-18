<?php

namespace Drupal\pagarme\Plugin\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class PagarmeModal extends PaymentOffsiteForm {

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

    $checkout_params = $pagarmeDrupal->processOrder();

    if ($plugin_config['pagarme_customer_data']) {
      $token = \Drupal::token();
      if (!empty($plugin_config['pagarme_cpf_token'])) {
        $checkout_params['customerDocumentNumber'] = $token->replace($plugin_config['pagarme_cpf_token'], array('commerce_order' => $order));
      } else if (!empty($plugin_config['pagarme_cnpj_token'])) {
        $checkout_params['customerDocumentNumber'] =$token->replace($plugin_config['pagarme_cnpj_token'], array('commerce_order' => $order));
      }

      $checkout_params['customerAddressStreetNumber'] = $token->replace($plugin_config['pagarme_street_number_token'], array('commerce_order' => $order));

      $checkout_params['customerPhoneDdd'] = $token->replace($plugin_config['pagarme_phone_ddd_token'], array('commerce_order' => $order));

      $checkout_params['customerPhoneNumber'] = $token->replace($plugin_config['pagarme_phone_number_token'], array('commerce_order' => $order));
    }

    $form['#attached']['library'][] = 'pagarme/pagarme_modal';

    $pagarme_encryption_key = $payment_gateway_plugin->getConfiguration()['pagarme_encryption_key'];
    $settings = array(
      'pagarme' => array(
        'encryption_key' => $pagarme_encryption_key,
        'checkout_params' => $checkout_params,
      ),
    );

    $form['#attached']['drupalSettings'] = $settings;

    $form['pagarme_modal'] = array(
      '#type' => 'container'
    );

    $form['pagarme_modal']['answer'] = array(
      '#attributes' => array('class' => 'pagarme-cp-answer'),
      '#type' => 'hidden',
    );

    $link = '<a href="" id="pagarme-modal-pay-button">' . $plugin_config['pagarme_display_title_pay_button'] . '</a>';
    $form['pagarme_modal']['link'] = array(
      '#type' => 'markup',
      '#markup' => $link,
    );

    $form['pagarme_modal']['messages'] = array(
      '#type' => 'markup',
      '#markup' => '<div id="pagarme-modal-messages"></div>',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $pagarme_answer = $values['pagarme_modal']['answer'];

    if (empty($pagarme_answer)) {
      $form_state->setError($form['pagarme_modal']['answer'], t('There was an error with Pagar.me. Please try again later.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $plugin_config = $payment_gateway_plugin->getConfiguration();

    $pagarmeDrupal = new \Drupal\pagarme\Pagarme\PagarmeDrupal($plugin_config['pagarme_api_key']);

    $pagarmeDrupal->setPluginConfiguration($plugin_config);

    $order = $payment->getOrder();
    $pagarmeDrupal->setOrder($order);

    $values = $form_state->getValue($form['#parents']);
    $pagarme_answer = json_decode($values['pagarme_modal']['answer']);

    if ($plugin_config['pagarme_debug']) {
      \Drupal::logger('pagarme')->debug(t('@payment_way: <pre>@pre</pre>'), array('@pre' => print_r($pagarme_answer, TRUE)));
    }

    // Dados do cliente
    $customer = array();
    if (!empty($pagarme_answer->customer)) {
      $customer = (array)$pagarme_answer->customer;
      if (!empty($customer['address'])) {
        $customer['address'] = (array) $customer['address'];
      }
      if (!empty($customer['phone'])) {
        $customer['phone'] = (array) $customer['phone'];
      }
    }

    $token = \Drupal::token();
    if (empty($customer['address']['street_number'])) {
      if (!empty($plugin_config['pagarme_street_number_token'])) {
        $customer['address']['street_number'] = $token->replace($plugin_config['pagarme_street_number_token'], array('commerce_order' => $order));
      }
    }

    if (empty($customer['document_number'])) {
      if (!empty($plugin_config['pagarme_cpf_token'])) {
        $customer['document_number'] = $token->replace($plugin_config['pagarme_cpf_token'], array('commerce_order' => $order));
      } else if (!empty($plugin_config['pagarme_cnpj_token'])) {
        $customer['document_number'] = $token->replace($plugin_config['pagarme_cnpj_token'], array('commerce_order' => $order));
      }
    }

    if (empty($customer['phone']['ddd'])) {
      if (!empty($plugin_config['pagarme_phone_ddd_token'])) {
        $customer['phone']['ddd']= $token->replace($plugin_config['pagarme_phone_ddd_token'], array('commerce_order' => $order));
      }
    }

    if (empty($customer['phone']['number'])) {
      if (!empty($plugin_config['pagarme_phone_number_token'])) {
        $customer['phone']['number'] = $token->replace($plugin_config['pagarme_phone_number_token'], array('commerce_order' => $order));
      }
    }

    if (empty($customer['gender'])) {
      if (!empty($plugin_config['pagarme_gender_token'])) {
        $customer['gender'] = $token->replace($plugin_config['pagarme_gender_token'], array('commerce_order' => $order));
      }
    }

    if (empty($customer['pagarme_birthday_token'])) {
      if (!empty($plugin_config['pagarme_birthday_token'])) {
        $customer_born_at = $token->replace($plugin_config['pagarme_birthday_token'], array('commerce_order' => $order));
        if ($plugin_config['pagarme_birthday_token'] !== $customer_born_at) {
          $customer['pagarme_birthday_token'] = date('m-d-Y', strtotime($customer_born_at));
        }
      }
    }

    \Drupal::moduleHandler()->alter('pagarme_customer', $customer, $order);
    $pagarmeDrupal->setCustomer($customer);

    $order->setData('pagarme_payment_method', $pagarme_answer->payment_method);

    try {
      switch ($pagarme_answer->payment_method) {

        case 'credit_card':
          $amount = $pagarme_answer->amount;
          $card_hash = $pagarme_answer->card_hash;
          $installments = $pagarme_answer->installments;

          $installments_amount = $pagarmeDrupal->calculateInstallmentsAmount();
          $order->setData('pagarme_installments_amount', $installments_amount[$installments]);

          $transaction = $pagarmeDrupal->creditCardTransaction($amount, $card_hash, $installments);

          if ($transaction->getStatus() == 'refused') {
            $message = t('Your purchase was declined, please check your payment information and try again or use another form of payment.');
            drupal_set_message($message, 'error');
            throw new \Exception($message);
          }
          break;

        case 'boleto':
          $transaction = $pagarmeDrupal->boletoTransaction($pagarme_answer->amount);
          $order->setData('pagarme_payment_boleto_url', $transaction->getBoletoUrl());
          break;
      }

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
