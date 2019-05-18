<?php

namespace Drupal\commerce_wechat_pay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\Core\Database\Database;

class MicropayForm extends PaymentOffsiteForm {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $payment = $this->entity;
    $order_id = $payment->getOrderId();
    $connection = Database::getConnection();
    $select = $connection->select('commerce_payment', 'cp')
      ->fields('cp', array('payment_id'))
      ->condition('cp.order_id', $order_id, '=');
    $data = $select->execute();
    $results = $data->fetchCol();
    $payment_ids = $results;

    $form['barcode'] = [
      '#type' => 'number',
      '#title' => $this->t('Barcode'),
      '#size' => 18
    ];

    $form['payment_ids'] = [
      '#type' => 'value',
      '#value' => $payment_ids,
    ];

    $form['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Submit'),
    ];

    $form['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
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

    $form_state_values = $form_state->getValues();
    $payment_ids = $form_state_values['payment_process']['offsite_payment']['payment_ids'];

    if ($form_state_values['op']->getUntranslatedString() == 'Submit') {
      // 18 digtis, must start with 11, 12, 13, 14 or 15
      if (preg_match('/^1(0|1|2|3|4|5)\d{16}$/', $values['barcode']) == 0) {
        $form_state->setError($form['barcode'], $this->t('Invalid barcode detected! The barcode should have 18 digits and it must start with 11, 12, 13, 14 or 15. The invalid barcode is: ') . $values['barcode']);
        return;
      }

      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = $this->entity;
      /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
      $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

      $order = $payment->getOrder();
      $price = $payment->getAmount();
      $store_id = $order->getStoreId();

      try {
        $payment_gateway_plugin->capture((string) $order->id(), $values['barcode'], $price, $store_id);

      } catch (\Exception $e) {
        // Payment is not successful
        \Drupal::logger('commerce_wechat_pay')->error($e->getMessage());
        $form_state->setError($form['barcode'], t('Commerce WeChat Pay is having problem to connect to WeChat servers: ') . $e->getMessage());
      }
    }

    if ($form_state_values['op']->getUntranslatedString() == 'Cancel') {
      $payment_id = NULL;
      if (count($payment_ids) == 1) {
        $payment_id = $payment_ids[0];
      }
      if (count($payment_ids) > 1) {
        drupal_set_message('Some thiings were wrong, this order has more than one payment, please check it.');
        return;
      }
      if ($payment_id) {
        $payment_entity = Payment::load($payment_id);
        $order_id = $payment_entity->getOrderId();
        $payment_gateway_plugin->cancel($payment_id, $order_id);
        return;
      } else {
        drupal_set_message('This order has not been paid.');
        return;
      }
    }

  }

}
