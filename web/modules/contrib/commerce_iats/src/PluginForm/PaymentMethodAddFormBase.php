<?php

namespace Drupal\commerce_iats\PluginForm;

use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PaymentMethodAddFormBase.
 */
abstract class PaymentMethodAddFormBase extends BasePaymentMethodAddForm {

  /**
   * Builds the hosted form.
   *
   * @param array $element
   *   The target element.
   * @param string $type
   *   Optional. The type of hosted form to build, "credit_card" (default) or
   *   "ach".
   *
   * @return array
   *   The built hosted form.
   */
  protected function buildHostedForm(array $element, $type = NULL) {
    // Normalize the type.
    switch ($type) {
      case 'ach':
        $type = 'Ach';
        break;

      default:
        $type = 'Card';
    }

    $config = $this->plugin->getConfiguration();
    $element['#type'] = 'container';
    $element['#attributes']['id'][] = 'checkout-embed';
    $element['#attributes']['class'][] = Html::getClass('commerce-iats-hosted');
    $element['#attributes']['data-transcenter'] = $config['transcenter'];
    $element['#attributes']['data-processor'] = $config['processor'];
    $element['#attributes']['data-type'] = 'Vault';
    $element['#attributes']['data-form'] = $type;
    $element['#attached']['library'][] = 'commerce_iats/cryptogram';
    return $element;
  }

  /**
   * Determines if the payment gateway is operating with hosted form processing.
   *
   * @return bool
   *   Indicates if the payment gateway is operating with hosted form
   *   processing.
   */
  protected function isHosted() {
    $config = $this->plugin->getConfiguration();
    return $config['processing_type'] == 'hosted';
  }

  /**
   * Captures the cryptogram and sets it into the form values.
   */
  protected function captureCryptogram(array &$element, FormStateInterface $form_state) {
    if (!$c = $this->getCryptogram()) {
      throw new DeclineException(t('We encountered an error processing your payment method. Please verify your details and try again.'));
    }

    $key = $element['#parents'];
    array_push($key, 'cryptogram');
    $form_state->setValue($key, $c);
  }

  /**
   * Gets the submitted cryptogram value.
   *
   * @return string
   *   The cryptogram value.
   */
  protected function getCryptogram() {
    return $this->getRequestStack()
      ->getCurrentRequest()
      ->request->get('checkout-cryptogram');
  }

  /**
   * Gets the request stack.
   *
   * @return \Symfony\Component\HttpFoundation\RequestStack
   *   The request stack.
   */
  protected function getRequestStack() {
    return \Drupal::requestStack();
  }

}
