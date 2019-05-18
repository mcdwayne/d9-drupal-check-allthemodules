<?php

namespace Drupal\commerce_payone\PluginForm\Sepa;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

class SepaPaymentMethodAddForm extends PaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['payment_details'] = $this->buildSepaMethodForm($form['payment_details'], $form_state);

    return $form;
  }

  /**
   * Builds the pagos net method form.
   *
   * @param array $element
   *   The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built credit card form.
   */
  protected function buildSepaMethodForm(array $element, FormStateInterface $form_state) {
    // TODO: Fill default_value from the billing information fields

    $element['iban'] = [
      '#type' => 'textfield',
      '#title' => t('IBAN'),
      '#required' => TRUE,
    ];

    $element['bic'] = [
      '#type' => 'textfield',
      '#title' => t('BIC'),
    ];

    return $element;
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['payment_details']['#parents']);
    $this->entity->iban = $values['iban'];
    $this->entity->bic = $values['bic'];

    parent::submitConfigurationForm($form, $form_state);
  }

}