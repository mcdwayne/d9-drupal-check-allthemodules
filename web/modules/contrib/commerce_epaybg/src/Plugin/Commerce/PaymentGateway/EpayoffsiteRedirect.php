<?php

namespace Drupal\commerce_epaybg\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Messenger\Messenger;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "EpayBG_offsite_redirect",
 *   label = "EpayBG (Redirect to EpayBG system)",
 *   display_label = "EpayBG",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_epaybg\PluginForm\EpayoffsiteRedirect\EpaypaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class EpayoffsiteRedirect extends OffsitePaymentGatewayBase {

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              PaymentTypeManager $payment_type_manager,
                              PaymentMethodTypeManager $payment_method_type_manager,
                              TimeInterface $time,  Messenger $messenger) {
    $this->messenger = $messenger;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('messenger')

    );
  }
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

    $form['epay_data'] = [

      'epay_desc_phrase' =>
        [
          '#type' => 'textfield',
          '#title' => $this->t('Epay description phrase'),
          '#default_value' => (isset($this->configuration['epay_data']['epay_desc_phrase'])) ?
            $this->configuration['epay_data']['epay_desc_phrase'] : '',
          '#size' => 60,
          '#maxlength' => 50,
          '#required' => TRUE,
        ],
    /**
     * Live mode EpayBG Fields
     */
    'live_email' =>
      [
        '#type' => 'email',
        '#title' => $this->t('Epay User Email'),
        '#default_value' => (isset($this->configuration['epay_data']['live_email'])) ?
          $this->configuration['epay_data']['live_email'] : '',
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
        '#default_value' => (isset($this->configuration['epay_data']['live_min'])) ?
          $this->configuration['epay_data']['live_min'] : '',
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
        '#default_value' => (isset($this->configuration['epay_data']['live_key'])) ?
          $this->configuration['epay_data']['live_key'] : '',
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
     * Test mode EpayBG Fields
     */
    'test_email' =>
      [
        '#type' => 'email',
        '#title' => $this->t('Test Epay User Email'),
        '#default_value' => (isset($this->configuration['epay_data']['test_email'])) ?
          $this->configuration['epay_data']['test_email'] : '',
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
        '#default_value' => (isset($this->configuration['epay_data']['test_min'])) ?
          $this->configuration['epay_data']['test_min'] : '',
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
        '#default_value' => (isset($this->configuration['epay_data']['test_key'])) ?
          $this->configuration['epay_data']['test_key'] : '',
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
       * Add payment mode to EpayBG config data
      */
      $values['epay_data']['mode'] = $this->getMode();

      $this->configuration['epay_data'] = $values['epay_data'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

    $epay_db_obj = \Drupal::database()->select('commerce_epaybg_payments')
                    ->condition('commerce_order_id', $order->id(), '=')
                    ->fields('commerce_epaybg_payments', ['invoice', 'epay_payment_total_price', 'commerce_epay_status'])
                    ->execute()
                    ->fetchObject();

    // @todo Add examples of request validation.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => $epay_db_obj->commerce_epay_status,
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
      'remote_id' => $epay_db_obj->invoice,
      'remote_state' => $epay_db_obj->commerce_epay_status,
      'authorized' => $this->time->getRequestTime(),
    ]);
    $payment->save();

    $this->messenger->addMessage(t('EpayBG Payment was processed'));
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $response_msg = '';

    $mode = $this->configuration['epay_data']['mode'];
    $encoded = $request->get('encoded');
    $checksum = $request->get('checksum');
    $secret = $this->configuration['epay_data'][$mode . '_key'];
    $epay_payment_data = commerce_epaybg_receive_data($encoded, $checksum, $secret);

    if(!empty($epay_payment_data)) {
      foreach($epay_payment_data as $epay_payment) {
        $epay_db_state = \Drupal::database()->update('commerce_epaybg_payments')
                          ->fields([
                            'commerce_epay_status' => $epay_payment['status'],
                          ])
                          ->condition('invoice', $epay_payment['invoice'], '=')
                          ->execute();

        if($epay_db_state) {
          $response_msg .= "INVOICE=" . $epay_payment['invoice'] . ":STATUS=OK\n";
        } else {
          $response_msg .= "INVOICE=" . $epay_payment['invoice'] . ":STATUS=ERR\n";
        }
      }
    } else {
      $response_msg = "ERR=Not valid CHECKSUM\n";
    }

    $response = new Response(
      $response_msg,
      Response::HTTP_OK,
      array('content-type' => 'text/plain')
    );

    $response->send();
  }
}
