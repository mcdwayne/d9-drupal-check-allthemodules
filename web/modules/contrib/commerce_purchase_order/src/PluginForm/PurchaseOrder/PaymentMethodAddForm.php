<?php

namespace Drupal\commerce_purchase_order\PluginForm\PurchaseOrder;

use Drupal\commerce_payment\PluginForm\PaymentGatewayFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\profile\Entity\Profile;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * PaymentMethodAddForm for Purchase Order.
 */
class PaymentMethodAddForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function getErrorElement(array $form, FormStateInterface $form_state) {
    return $form['payment_details'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'commerce_payment/payment_method_form';
    $form['#tree'] = TRUE;
    $form['payment_details'] = [
      '#parents' => array_merge($form['#parents'], ['payment_details']),
      '#type' => 'container',
      '#payment_method_type' => 'purchase_order',
    ];
    $form['payment_details'] = $this->buildPurchaseOrderForm($form['payment_details'], $form_state);
    $form['billing_information'] = [
      '#parents' => array_merge($form['#parents'], ['billing_information']),
      '#type' => 'container',
    ];
    $form['billing_information'] = $this->buildBillingProfileForm($form['billing_information'], $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // No extra validation on the p.o. number field.
    $this->validateBillingProfileForm($form['billing_information'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->submitBillingProfileForm($form['billing_information'], $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    // The payment method form is customer facing. For security reasons
    // the returned errors need to be more generic.
    // The Purchase Order Gateway will handle processing the form values.
    try {
      $payment_gateway_plugin->createPaymentMethod($payment_method, $values['payment_details']);
    }
    catch (DeclineException $e) {
      \Drupal::logger('commerce_payment')->warning($e->getMessage());
      throw new DeclineException('We encountered an error processing your payment method. Please verify your details and try again.');
    }
    catch (PaymentGatewayException $e) {
      \Drupal::logger('commerce_payment')->error($e->getMessage());
      throw new PaymentGatewayException('We encountered an unexpected error processing your payment method. Please try again later.');
    }
  }

  /**
   * Builds the purchase order form.
   *
   * @param array $element
   *   The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built form.
   */
  protected function buildPurchaseOrderForm(array $element, FormStateInterface $form_state) {
    $element['#attributes']['class'][] = 'purchase-order-form';
    $element['number'] = [
      '#type' => 'textfield',
      '#title' => t('Purchase Order number'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 19,
      '#size' => 20,
    ];
    return $element;
  }

  /**
   * Builds the billing profile form.
   *
   * @param array $element
   *   The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built billing profile form.
   */
  protected function buildBillingProfileForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
    $billing_profile = Profile::create([
      'type' => 'customer',
      'uid' => $payment_method->getOwnerId(),
    ]);
    $form_display = EntityFormDisplay::collectRenderDisplay($billing_profile, 'default');
    $form_display->buildForm($billing_profile, $element, $form_state);
    // Remove the details wrapper from the address field.
    if (!empty($element['address']['widget'][0])) {
      $element['address']['widget'][0]['#type'] = 'container';
    }
    // Store the billing profile for the validate/submit methods.
    $element['#entity'] = $billing_profile;

    return $element;
  }

  /**
   * Validates the billing profile form.
   *
   * @param array $element
   *   The billing profile form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function validateBillingProfileForm(array &$element, FormStateInterface $form_state) {
    $billing_profile = $element['#entity'];
    $form_display = EntityFormDisplay::collectRenderDisplay($billing_profile, 'default');
    $form_display->extractFormValues($billing_profile, $element, $form_state);
    $form_display->validateFormValues($billing_profile, $element, $form_state);
  }

  /**
   * Handles the submission of the billing profile form.
   *
   * @param array $element
   *   The billing profile form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function submitBillingProfileForm(array $element, FormStateInterface $form_state) {
    $billing_profile = $element['#entity'];
    $form_display = EntityFormDisplay::collectRenderDisplay($billing_profile, 'default');
    $form_display->extractFormValues($billing_profile, $element, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    $payment_method->setBillingProfile($billing_profile);
  }

}
