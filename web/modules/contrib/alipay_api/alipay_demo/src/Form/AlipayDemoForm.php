<?php

namespace Drupal\alipay_demo\Form;

use \Drupal\Core\Form\FormBase;
use \Drupal\Core\Form\FormStateInterface;

/**
 * Returns responses for alipay_demo module routes.
 */
class AlipayDemoForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alipay_demo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['out_trade_no'] = array(
      '#title' => $this->t('Order ID'),
      '#type' => 'textfield',
      '#default_value' => rand(1000000, 9999999),
      '#description' => $this->t('Each order need an unique ID, this demo use a random number as Order ID'),
      '#required' => TRUE,
    );

    $form['subject'] = array(
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => $this->t('Alipay Demo'),
      '#description' => $this->t('Product or service name'),
      '#required' => TRUE,
    );

    $form['total_fee'] = array(
      '#title' => $this->t('Total fee'),
      '#type' => 'textfield',
      '#default_value' => 0.01,
      '#description' => $this->t('Total amount of this order'),
      '#required' => TRUE,
    );

    $form['body'] = array(
      '#title' => $this->t('Description'),
      '#type' => 'textfield',
      '#default_value' => $this->t('This is an Alipay demo'),
      '#description' => $this->t('Detail information about the product or service'),
      '#required' => TRUE,
    );

    $form['submit'] = array(
      '#title' => $this->t('Submit'),
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $total_fee = $form_state->getValue('total_fee');
    if (!is_numeric($total_fee)) {
      $form_state->setErrorByName('total_fee', $this->t('Invalid total fee value'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    alipay_api_pay($values['out_trade_no'], $values['subject'], $values['total_fee'], $values['body']);
  }

}
