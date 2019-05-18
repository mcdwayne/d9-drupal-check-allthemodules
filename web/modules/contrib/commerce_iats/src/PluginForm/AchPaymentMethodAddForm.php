<?php

namespace Drupal\commerce_iats\PluginForm;

use Drupal\commerce_iats\Plugin\Commerce\PaymentMethodType\CommerceIatsAch;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AchPaymentMethodAddForm.
 */
class AchPaymentMethodAddForm extends PaymentMethodAddFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    if ($this->isHosted()) {
      $form['payment_details'] = $this->buildHostedForm($form['payment_details'], 'ach');
    }
    else {
      $form['payment_details'] = $this->buildAchForm($form['payment_details']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Hosted, capture the cryptogram.
    if ($this->isHosted()) {
      $this->captureCryptogram($form['payment_details'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildAchForm($element) {
    $element['routing_number'] = [
      '#type' => 'textfield',
      '#title' => t('Routing number'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 9,
      '#size' => 20,
    ];

    $element['account_number'] = [
      '#type' => 'textfield',
      '#title' => t('Account number'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 12,
      '#size' => 20,
    ];

    $element['account_type'] = [
      '#type' => 'select',
      '#title' => t('Account type'),
      '#required' => TRUE,
      '#options' => CommerceIatsAch::accountTypes(),
    ];

    return $element;
  }

}
