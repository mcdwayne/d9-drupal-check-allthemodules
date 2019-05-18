<?php

namespace Drupal\commerce_dibs\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_dibs_offsite_redirect",
 *   label = "DIBS (Off-site redirect)",
 *   display_label = @Translation("Pay on DIBS"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_dibs\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 * )
 */
class OffsiteRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant' => '',
      'pay_type' => '',
      'account' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $dibs_pay_type_url = new FormattableMarkup('<a href="http://tech.dibspayment.com/D2/Toolbox/Paytypes">http://tech.dibspayment.com/D2/Toolbox/Paytypes</a>', []);

    $form['merchant'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#description' => $this->t('The Merchant ID is the DIBS Customer ID that appears in the e-mail received from DIBS during registration with DIBS, on your contract, or in the invoice from DIBS. If you do not have your Merchant ID, please contact DIBS support department.'),
      '#default_value' => $this->configuration['merchant'],
      '#required' => TRUE,
    ];
    $form['pay_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pay type'),
      '#description' => $this->t('A comma separated list of payment type short names. Example: "VISA,MC,AMEX,MTRO,ELEC". See @url for all possible pay types.', ['@url' => $dibs_pay_type_url]),
      '#default_value' => $this->configuration['pay_type'],
    ];
    $form['account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account'),
      '#description' => $this->t('If multiple departments utilize the same DIBS merchant, it may be practical to keep the transactions separate at DIBS. An account name may be inserted in this field, to separate transactions at DIBS. To get an account, please contact the DIBS sales department.'),
      '#default_value' => $this->configuration['account'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant'] = $values['merchant'];
      $this->configuration['pay_type'] = $values['pay_type'];
      $this->configuration['account'] = $values['account'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $state = $request->get('status');

    if ($state == 'ACCEPTED') {
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      $payment = $payment_storage->create([
        'state' => 'authorization',
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $this->entityId,
        'order_id' => $order->id(),
        'remote_state' => $state,
        'remote_id' => $request->get('transaction'),
      ]);
      $payment->save();
    }
    else {
      throw new PaymentGatewayException($state);
    }
  }

}
