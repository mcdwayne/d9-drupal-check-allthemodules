<?php

namespace Drupal\commerce_clic\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * {@inheritdoc}
 */
class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  protected $integrityHash;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_clic\Plugin\Commerce\PaymentGateway\ClicInterface $plugin */
    $plugin = $payment->getPaymentGateway()->getPlugin();
    $order = $payment->getOrder();
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billingAddress */

    if ($plugin->getMode() == 'live') {
      $form['#attached']['library'][] = 'commerce_clic/clic_widget_live';
    }
    else {
      $form['#attached']['library'][] = 'commerce_clic/clic_widget_test';
    }

    $form['#attached']['library'][] = 'commerce_clic/clic_form';

    $options = [
      'public_key' => $plugin->getPublicKey(),
      'return_url' => $form['#return_url'],
      'cancel_url' => $form['#cancel_url'],
      'transaction' => [
        'amount' => (float) $payment->getAmount()->getNumber(),
        'currency' => $payment->getAmount()->getCurrencyCode(),
        'email' => $order->getEmail(),
        'orderId' => $order->id(),
        'customData' => [
          // Clic doesn't return a currency on IPN.
          // @see \Drupal\commerce_clic\Plugin\Commerce\PaymentGateway::onNotify().
          'currency' => $payment->getAmount()->getCurrencyCode(),
        ],
      ],
    ];

    $form['widget'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'clic-widget',
      ],
    ];

    $form = $this->buildRedirectForm($form, $form_state, '', $options, '');
    $form['#attached']['drupalSettings']['commerce_clic']['transactionData'] = $options;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, $redirect_url, array $data, $redirect_method = BasePaymentOffsiteForm::REDIRECT_GET) {
    $form['commerce_message'] = [
      '#prefix' => '<div class="checkout-help">',
      '#suffix' => '</div>',
      '#markup' => t('Please wait while the payment server loads. If nothing happens within 10 seconds, please click on the button below.'),
      // Plugin forms are embedded using #process, so it's too late to attach
      // another #process to $form itself, it must be on a sub-element.
      '#process' => [
        [get_class($this), 'processRedirectForm'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function processRedirectForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    $complete_form['#attributes']['class'][] = 'payment-redirect-form';
    unset($element['#action']);
    // The form actions are hidden by default, but needed in this case.
    $complete_form['actions']['#access'] = TRUE;
    foreach (Element::children($complete_form['actions']) as $element_name) {
      $complete_form['actions'][$element_name]['#access'] = TRUE;
    }

    return $element;
  }

}
