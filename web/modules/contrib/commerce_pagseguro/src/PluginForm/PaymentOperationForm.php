<?php

namespace Drupal\commerce_pagseguro\PluginForm;

use Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentGateway\Pagseguro;
use Drupal\commerce_payment\PluginForm\PaymentOperationForm as BasePaymentOperationForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\Order;
use PagSeguro\Library;
use PagSeguro\Configuration;
use PagSeguro\Services;

class PaymentOperationForm extends BasePaymentOperationForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $payment_method = $this->entity;

    $form['testing'] = [
      '#type' => 'textfield',
      '#title' => t('Helloooo'),
      '#attributes' => [
        'autocomplete' => 'off',
        'id' => 'holder-name'
      ],
      '#required' => TRUE,
    ];

    return $form;
  }

   /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    exit("testing");
  }
}