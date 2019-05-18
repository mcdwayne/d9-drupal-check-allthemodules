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
 *   id = "commerce_dibs_flexwin_offsite_redirect",
 *   label = "DIBS FlexWin (Off-site redirect)",
 *   display_label = @Translation("Pay on DIBS"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_dibs\PluginForm\OffsiteRedirect\FlexWinPaymentOffsiteForm",
 *   },
 * )
 */
class FlexWinOffsiteRedirect extends OffsitePaymentGatewayBase {

  /**
   * The transaction is approved by acquirer.
   */
  const AUTHORIZATION_APPROVED = 2;

  /**
   * The transaction capture is approved by the acquirer.
   */
  const CAPTURE_APPROVED = 5;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant' => '',
      'pay_type' => '',
      'account' => '',
      'decorator' => 'default',
      'md5_key' => FALSE,
      'md5_key1' => '',
      'md5_key2' => '',
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
    $form['decorator'] = [
      '#type' => 'select',
      '#title' => $this->t('Decorator'),
      '#description' => $this->t('Layout of FlexWin'),
      '#default_value' => $this->configuration['decorator'],
      '#options' => [
        'default' => 'Default',
        'basal' => 'Basal',
        'rich' => 'Rich',
        'responsive' => 'Responsive',
      ],
    ];
    $form['md5_key'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('MD5 key'),
      '#description' => $this->t('If the solution requires an md5 key, please check off here.'),
      '#default_value' => $this->configuration['md5_key'],
    ];
    $form['md5_key1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MD5 k1 value'),
      '#description' => $this->t('Insert k1 value here.'),
      '#default_value' => $this->configuration['md5_key1'],
      '#states' => [
        'visible' => [
          ':input[name$="[md5_key]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['md5_key2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MD5 k2 value'),
      '#description' => $this->t('Insert k2 value here.'),
      '#default_value' => $this->configuration['md5_key2'],
      '#states' => [
        'visible' => [
          ':input[name$="[md5_key]"]' => ['checked' => TRUE],
        ],
      ],
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
      $this->configuration['decorator'] = $values['decorator'];
      $this->configuration['md5_key'] = $values['md5_key'];
      $this->configuration['md5_key1'] = $values['md5_key1'];
      $this->configuration['md5_key2'] = $values['md5_key2'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $state = $request->get('statuscode');
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    switch ($state) {
      case static::AUTHORIZATION_APPROVED:
        $payment = $payment_storage->create([
          'state' => 'authorization',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $this->entityId,
          'order_id' => $order->id(),
          'remote_state' => $state,
          'remote_id' => $request->get('transact'),
        ]);
        $payment->save();
        break;

      case static::CAPTURE_APPROVED:
        $payment = $payment_storage->create([
          'state' => 'completed',
          'authorized' => $this->time->getCurrentTime(),
          'completed' => $this->time->getCurrentTime(),
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $this->entityId,
          'order_id' => $order->id(),
          'remote_state' => $state,
          'remote_id' => $request->get('transact'),
        ]);
        $payment->save();
        break;

      default:
        throw new PaymentGatewayException("Invalid state $state");
    }
  }

}
