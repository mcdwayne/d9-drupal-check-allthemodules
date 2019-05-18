<?php

namespace Drupal\commerce_easypaybg\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "EasyPayBG_offsite_redirect",
 *   label = "EasyPayBG (Get EasyPayBG payment code)",
 *   display_label = "EasyPayBG",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_easypaybg\PluginForm\OffsiteRedirect\EasyPayBgPaymentOffsiteForm",
 *   }
 * )
 */
class EasyPayBgOffsiteRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'redirect_method' => 'post',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // A real gateway would always know which redirect method should be used

    $form['easypay_data'] = [

    'easypay_desc_phrase' =>
      [
        '#type' => 'textfield',
        '#title' => $this->t('EasyPay description phrase'),
        '#default_value' => (isset($this->configuration['easypay_data']['easypay_desc_phrase'])) ?
          $this->configuration['easypay_data']['easypay_desc_phrase'] : '',
        '#size' => 60,
        '#maxlength' => 50,
        '#required' => TRUE,
      ],
    /**
     * Live mode EasyPayBG Fields
     */
    'live_email' =>
      [
        '#type' => 'email',
        '#title' => $this->t('Epay User Email'),
        '#default_value' => (isset($this->configuration['easypay_data']['live_email'])) ?
          $this->configuration['easypay_data']['live_email'] : '',
        '#size' => 60,
        '#maxlength' => 50,
        '#states' => [
          'visible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'live'],
          ],
          'invisible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'test'],
          ],
          'required' => [
            ':input[name="configuration[mode]"]' => ['value' => 'live'],
          ],
        ],
      ],
    'live_min' =>
      [
        '#type' => 'textfield',
        '#title' => $this->t('Epay User ID(min)'),
        '#default_value' => (isset($this->configuration['easypay_data']['live_min'])) ?
          $this->configuration['easypay_data']['live_min'] : '',
        '#size' => 60,
        '#maxlength' => 15,
        '#states' => [
          'visible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'live'],
          ],
          'invisible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'test'],
          ],
          'required' => [
            ':input[name="configuration[mode]"]' => ['value' => 'live'],
          ],
        ],      
      ],
    'live_key' =>
      [
        '#type' => 'textfield',
        '#title' => $this->t('Epay Secret Key'),
        '#default_value' => (isset($this->configuration['easypay_data']['live_key'])) ?
          $this->configuration['easypay_data']['live_key'] : '',
        '#size' => 60,
        '#maxlength' => 75,
        '#states' => [
          'visible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'live'],
          ],
          'invisible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'test'],
          ],
          'required' => [
            ':input[name="configuration[mode]"]' => ['value' => 'live'],
          ],
        ],
      ],
    /**
     * Test mode EasyPayBG Fields
     */  
    'test_email' =>
      [
        '#type' => 'email',
        '#title' => $this->t('Test Epay User Email'),
        '#default_value' => (isset($this->configuration['easypay_data']['test_email'])) ?
          $this->configuration['easypay_data']['test_email'] : '',
        '#size' => 60,
        '#maxlength' => 50,
        '#states' => [
          'invisible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'live'],
          ],
          'visible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'test'],
          ],
          'required' => [
            ':input[name="configuration[mode]"]' => ['value' => 'test'],
          ],
        ],
      ],
    'test_min' =>
      [
        '#type' => 'textfield',
        '#title' => $this->t('Test Epay User ID(min)'),
        '#default_value' => (isset($this->configuration['easypay_data']['test_min'])) ?
          $this->configuration['easypay_data']['test_min'] : '',
        '#size' => 60,
        '#maxlength' => 15,
        '#states' => [
          'invisible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'live'],
          ],
          'visible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'test'],
          ],
          'required' => [
            ':input[name="configuration[mode]"]' => ['value' => 'test'],
          ],
        ],      
      ],
    'test_key' =>
      [
        '#type' => 'textfield',
        '#title' => $this->t('Test Epay Secret Key'),
        '#default_value' => (isset($this->configuration['easypay_data']['test_key'])) ?
          $this->configuration['easypay_data']['test_key'] : '',
        '#size' => 60,
        '#maxlength' => 75,
        '#states' => [
          'invisible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'live'],
          ],
          'visible' => [
            ':input[name="configuration[mode]"]' => ['value' => 'test'],
          ],
          'required' => [
            ':input[name="configuration[mode]"]' => ['value' => 'test'],
          ],
        ],
      ],
    'easypay_deadline' => 
      [
        '#type' => 'textfield',
        '#title' => t('Order payment deadline'),
        '#description' => t('Deadline for payment - set between 1 and 30 days.'),
        '#default_value' => (isset($this->configuration['easypay_data']['easypay_deadline'])) ?
          $this->configuration['easypay_data']['easypay_deadline'] : '',
        '#required' => TRUE,
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
      
      /**
       * Add payment mode to EasypayBG config data
      */ 
      $values['easypay_data']['mode'] = $this->getMode();

      $this->configuration['easypay_data'] = $values['easypay_data'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

    $easypay_db_obj = \Drupal::database()->select('commerce_easypaybg_payments')
                    ->condition('commerce_order_id', $order->id(), '=')
                    ->fields('commerce_easypaybg_payments', ['easypay_code', 'status', 'pay_time'])
                    ->execute()
                    ->fetchObject();

    // @todo Add examples of request validation.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => $easypay_db_obj->status,
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
      'remote_id' => $easypay_db_obj->easypay_code,
      'remote_state' => $easypay_db_obj->status,
      'authorized' => REQUEST_TIME,
    ]);
    $payment->save();
    drupal_set_message("EasyPayBG Payment was processed. The code for payment to EasyPay is {$easypay_db_obj->easypay_code}.Payment in EasyPay's office must be made before {$easypay_db_obj->pay_time}");
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $response_msg = '';

    $mode = $this->configuration['easypay_data']['mode'];
    $encoded = $request->get('encoded');
    $checksum = $request->get('checksum');
    $secret = $this->configuration['easypay_data'][$mode . '_key'];
    $easypay_payment_data = commerce_easypaybg_receive_data($encoded, $checksum, $secret);

    if(!empty($easypay_payment_data)) {
      foreach($easypay_payment_data as $easypay_payment) {
        $easypay_db_state = \Drupal::database()->update('commerce_easypaybg_payments')
                          ->fields([
                            'status' => $easypay_payment['status'],
                          ])
                          ->condition('invoice', $easypay_payment['invoice'], '=')
                          ->execute();

        $easypay_db_obj = \Drupal::database()->select('commerce_easypaybg_payments')
                        ->condition('invoice', $easypay_payment['invoice'], '=')
                        ->fields('commerce_easypaybg_payments', ['easypay_code', 'status'])
                        ->execute()
                        ->fetchObject();

        if($easypay_db_obj) {

          $payment_db_state = \Drupal::database()->update('commerce_payment')
                            ->fields([
                              'state' => $easypay_db_obj->status,
                              'remote_state' => $easypay_db_obj->status,
                            ])
                            ->condition('remote_id', $easypay_db_obj->easypay_code, '=')
                            ->execute();

          if($payment_db_state) {
            $response_msg .= "INVOICE=" . $easypay_payment['invoice'] . ":STATUS=OK\n";
          } else {
            $response_msg .= "INVOICE=" . $easypay_payment['invoice'] . ":STATUS=ERR\n"; 
          }

        } else {
          $response_msg .= "INVOICE=" . $easypay_payment['invoice'] . ":STATUS=ERR\n";
        }
      }
    } else {
      $response_msg = "ERR=Not valid CHECKSUM\n";
    }
    echo $response_msg; exit;
  }
}
