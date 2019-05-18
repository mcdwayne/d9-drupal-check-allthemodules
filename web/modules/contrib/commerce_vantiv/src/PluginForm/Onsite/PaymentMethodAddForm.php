<?php

namespace Drupal\commerce_vantiv\PluginForm\Onsite;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\commerce_vantiv\VantivApiHelper;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * The payment method 'add' form for Commerce Vantiv.
 *
 * @package commerce_vantiv
 */
class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'credit_card') {
      $this->submitCreditCardForm($form['payment_details'], $form_state);
    }
    elseif ($payment_method->bundle() == 'paypal') {
      $this->submitPayPalForm($form['payment_details'], $form_state);
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    $payment_method->setBillingProfile($form['billing_information']['#profile']);

    // Get values from POST instead of form_state (the only change from parent).
    $values = $this->getPostValues($form['#parents']);
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    // The payment method form is customer facing. For security reasons
    // the returned errors need to be more generic.
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
   * {@inheritdoc}
   */
  public function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    // @todo Do not validate if ajax
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#ajax'])) {
      return TRUE;
    }
    $values = $this->getPostValues($element['#parents']);
    $vantiv_card_type = $values['vantivResponseType'];
    $commerce_card_type = VantivApiHelper::getCommerceCreditCardType($vantiv_card_type);
    if (!$commerce_card_type) {
      // (if values doesn't have response$type).
      // (seems to happen when adding new credit card when one already exists).
      $form_state->setError($element['number'], t('Invalid credit card type.'));
      return;
    }
    $card_type = CreditCard::getType($commerce_card_type);
    if (!$card_type) {
      $form_state->setError($element['number'], t('You have entered a credit card number of an unsupported card type.'));
      return;
    }
    if (!CreditCard::validateExpirationDate($values['expiration']['month'], $values['expiration']['year'])) {
      $form_state->setError($element['expiration'], t('You have entered an expired credit card.'));
    }
    $form_state->setValueForElement($element['type'], $card_type->getId());
  }

  /**
   * {@inheritdoc}
   */
  public function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    /** @var PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    $values = $this->getPostValues($element['#parents']);
    $payment_method->card_type = VantivApiHelper::getCommerceCreditCardType($values['vantivResponseType']);
    $payment_method->card_number = $values['vantivResponseLastFour'];
    $payment_method->card_exp_month = $values['expiration']['month'];
    $payment_method->card_exp_year = $values['expiration']['year'];
    $payment_method->setRemoteId($values['vantivResponsePaypageRegistrationId']);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {

    /** @var OnSite $plugin */
    $plugin = $this->plugin;
    $configuration = $plugin->getConfiguration();
    // @todo Get order if it exists.
    // $order = $form_state->getValue('order');
    $element = parent::buildCreditCardForm($element, $form_state);

    // Add css class so that we can easily identify Vantiv related input fields;
    // Do not require the fields; Remove "name" attributes from Vantiv related
    // input elements to prevent card data to be sent to Drupal server.
    $credit_card_fields = ['number', 'security_code'];
    foreach ($credit_card_fields as $key) {
      $credit_card_field = &$element[$key];
      $credit_card_field['#attributes']['class'][] = 'commerce-vantiv-creditcard';
      $credit_card_field['#required'] = FALSE;
      $credit_card_field['#post_render'][] = [$this, 'removeFormElementName'];
    }

    // Add our hidden request value fields.
    $element['vantivRequestPaypageId'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => 'vantivRequestPaypageId'],
      '#value' => $configuration['paypage_id'],
    ];
    $element['vantivRequestMerchantTxnId'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => 'vantivRequestMerchantTxnId'],
      '#value' => $configuration['currency_merchant_map']['default'],
    ];
    $element['vantivRequestOrderId'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => 'vantivRequestOrderId'],
      '#value' => (!empty($order) && isset($order->order_id)) ? $order->order_id : 0,
    ];
    $element['vantivRequestReportGroup'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => 'vantivRequestReportGroup'],
      '#value' => $configuration['report_group'],
    ];

    // Attach and configure the front-end eProtect functionality.
    if ($library = $plugin->getJsLibrary()) {
      $element['#attached']['library'][] = $library;
      $element['#attached']['drupalSettings']['commerce_vantiv']['eprotect'] = [
        'mode' => $plugin->getMode(),
        'parents' => $element['#parents'],
      ];
    }

    // Add hidden response fields for storing information returned by Vantiv.
    foreach ([
      'vantivResponsePaypageRegistrationId',
      'vantivResponseBin',
      'vantivResponseCode',
      'vantivResponseMessage',
      'vantivResponseTime',
      'vantivResponseType',
      'vantivResponseLitleTxnId',
      'vantivResponseFirstSix',
      'vantivResponseLastFour',
    ] as $eprotectfield) {
      $element[$eprotectfield] = [
        '#type' => 'hidden',
        '#value' => '',
        '#attributes' => [
          'id' => $eprotectfield,
        ],
      ];
    }

    return $element;
  }

  /**
   * Gets values of the given parents from the request's POST parameters.
   *
   * @param array $parents
   *   The path to the requested values.
   *
   * @return mixed
   *   The value(s) from the request's POST parameters.
   */
  protected function getPostValues(array $parents) {
    $post_values = \Drupal::request()->request->all();

    return NestedArray::getValue($post_values, $parents);
  }

  /**
   * Removes the 'name' property from a form element.
   *
   * @param string $content
   *   The HTML string.
   * @param array $element
   *   The Drupal render element.
   *
   * @return string
   *   The HTML element with the 'name' property removed.
   */
  public function removeFormElementName($content, array $element) {
    $name_pattern = '/\sname\s*=\s*[\'"]?' . preg_quote($element['#name']) . '[\'"]?/';
    return preg_replace($name_pattern, '', $content);
  }

}
