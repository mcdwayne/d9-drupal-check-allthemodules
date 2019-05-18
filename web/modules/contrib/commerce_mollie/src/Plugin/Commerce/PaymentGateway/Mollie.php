<?php

namespace Drupal\commerce_mollie\Plugin\Commerce\PaymentGateway;

use Drupal;
use Drupal\commerce_mollie\ErrorHelper;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\HasPaymentInstructionsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Mollie\Api\Exceptions\ApiException as MollieApiException;
use Mollie\Api\Types\PaymentStatus as MolliePaymentStatus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Mollie payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "mollie",
 *   label = "Mollie",
 *   display_label = "Mollie",
 *   forms = {
 *     "offsite-payment" =
 *   "Drupal\commerce_mollie\PluginForm\OffsiteRedirect\MolliePaymentOffsiteForm",
 *   },
 *   payment_method_types = {"mollie"},
 * )
 */
class Mollie extends OffsitePaymentGatewayBase implements HasPaymentInstructionsInterface {

  /**
   * The Mollie gateway used for making API calls.
   *
   * @var \Mollie\Api\MollieApiClient
   */
  protected $api;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    // MollieAPIClient should be setup in the payment_method?
    $this->api = Drupal::service('commerce_mollie.mollie.api');

    // Initialize api_key.
    try {
      if (
        !empty($configuration['api_key_live'])
        && $configuration['api_key_live'] !== $this->defaultConfiguration()['api_key_live']
        && $configuration['mode'] === 'live'
      ) {
        $this->api->setApiKey($configuration['api_key_live']);
      }
      elseif (
        !empty($configuration['api_key_test'])
        && $configuration['api_key_test'] !== $this->defaultConfiguration()['api_key_test']
        && $configuration['mode'] === 'test'
      ) {
        $this->api->setApiKey($configuration['api_key_test']);
      }
    }
    catch (MollieApiException $e) {
      ErrorHelper::handleException($e);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = [
      'api_key_test' => 'test_',
      'api_key_live' => 'live_',
      'callback_domain' => '',
    ];
    return $default_configuration + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /*
     * Set a warning message when the default order-type workflow does not have
     * a validation step.
     */
    /** @var \Drupal\commerce_order\Entity\OrderType $order_type */
    $order_workflow = Drupal::config('commerce_order.commerce_order_type.default')
      ->get('workflow');
    if (strpos($order_workflow, 'validation') === FALSE) {
      $link = Link::fromTextAndUrl($this->t('Configure Order Types workflow'), Url::fromRoute('entity.commerce_order_type.collection'))->toString();
      Drupal::messenger()->addWarning($this->t('It is recommended to configure a workflow with a Validation step. @link', ['@link' => $link]));
    }

    // Build the form.
    $host = Drupal::request()->getHost();

    $form['api_key_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mollie Test API-key'),
      '#default_value' => $this->configuration['api_key_test'],
      '#required' => TRUE,
    ];
    $form['api_key_live'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mollie Live API-key'),
      '#default_value' => $this->configuration['api_key_live'],
      '#required' => TRUE,
    ];
    $form['callback_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callback domain'),
      '#description' => $this->t('Domain that Mollie should use to post transaction results to.<br/>
      <ul>
        <li> Do not add an "/" after .com</li>
        <li>You must include https:// or http://</li>
      </ul>
      For live websites this is the your live domain</br><br/>
      
      By default (empty) the domain visible in the browser window will be used.<br/>
      Currently that is: @host<br/><br/>
      
      While developing you can use a tunneled domain that hits your local development machine.<br/>
      This can be done with https://localtunnel.github.io/www<br/>
      <code>lt --port 80 --local-host @host</code>
      ', ['@host' => $host]),
      '#default_value' => $this->configuration['callback_domain'],
      '#required' => FALSE,
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
      $this->configuration['api_key_test'] = $values['api_key_test'];
      $this->configuration['api_key_live'] = $values['api_key_live'];
      $this->configuration['callback_domain'] = $values['callback_domain'];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {

    /*
     * Handle status that Mollie returns.
     */
    $mollie_payment_remote_id = $request->get('id');

    /** @var \Drupal\commerce_payment\PaymentStorage $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $payment = $payment_storage->loadByRemoteId($mollie_payment_remote_id);

    // Only proceed if payment is created.
    if ($payment === NULL) {
      return new JsonResponse();
    }

    // Evaluate the remote status.
    /** @var \Mollie\Api\Resources\Payment $mollie_payment_remote_object */
    $mollie_payment_remote_object = $this->getApi()->payments->get($mollie_payment_remote_id);

    // Commerce payment and order state.
    switch ($mollie_payment_remote_object->status) {
      case MolliePaymentStatus::STATUS_PAID:
        // Capture payment.
        $payment_transition = $payment->getState()->getWorkflow()->getTransition('authorize_capture');
        $payment->getState()->applyTransition($payment_transition);
        break;

      case MolliePaymentStatus::STATUS_CANCELED:
        // Cancel payment.
        $payment_transition = $payment->getState()->getWorkflow()->getTransition('void');
        $payment->getState()->applyTransition($payment_transition);
        break;

      case MolliePaymentStatus::STATUS_OPEN:
        // Authorize payment.
        $payment_transition = $payment->getState()->getWorkflow()->getTransition('authorize');
        $payment->getState()->applyTransition($payment_transition);
        break;

      case MolliePaymentStatus::STATUS_FAILED:
        // Void payment.
        $payment_transition = $payment->getState()->getWorkflow()->getTransition('void');
        $payment->getState()->applyTransition($payment_transition);
        break;

      case MolliePaymentStatus::STATUS_EXPIRED:
        // Expire payment.
        $payment_transition = $payment->getState()->getWorkflow()->getTransition('expire');
        $payment->getState()->applyTransition($payment_transition);
        break;
    }

    $payment->setRemoteState($mollie_payment_remote_object->status);
    $payment->save();

    // Return empty response with 200 status code.
    return new JsonResponse();

  }

  /**
   * Return initalized Mollie API.
   */
  public function getApi() {
    return $this->api;
  }

  /**
   * {@inheritdoc}
   */
  public function createRemotePayment(PaymentInterface $payment, $data) {

    // Prepare data for remote payment creation.
    $transaction_data = [
      'amount' => [
        'currency' => $payment->getAmount()->getCurrencyCode(),
        'value' => number_format($payment->getAmount()->getNumber(), 2, '.', ''),
      ],
      'description' => $this->t('@store order @order_id', [
        '@store' => $payment->getOrder()->getStore()->label(),
        '@order_id' => $payment->getOrderId(),
      ]),
      'redirectUrl' => $data['return'],
      'webhookUrl' => $this->getNotifyUrl()->toString(),
      'metadata' => [
        'order_id' => $payment->getOrderId(),
      ],
    ];

    // Create remote payment and save remote data in the drupal payment.
    try {
      $mollie_payment = $this->getApi()->payments->create($transaction_data);
      $payment->setRemoteId($mollie_payment->id);
      $payment->setRemoteState($mollie_payment->status);
      $payment->save();

      ErrorHelper::handleErrors($mollie_payment);
    }
    catch (MollieApiException $e) {
      ErrorHelper::handleException($e);
    }

    return $mollie_payment;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotifyUrl() {
    return Url::fromRoute('commerce_payment.notify', [
      'commerce_payment_gateway' => $this->entityId,
    ], [
      'absolute' => TRUE,
      'base_url' => $this->configuration['callback_domain'],
    ]);
  }

  /**
   * Builds the payment instructions.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   *
   * @return array
   *   A render array containing the payment instructions.
   */
  public function buildPaymentInstructions(PaymentInterface $payment = NULL) {
    $instructions = [
      '#type' => 'processed_text',
      '#text' => $this->t('Thank you for your payment with @gateway. We will inform you when your payment is processed. This is usually done within 24 hours.',
        ['@gateway' => $this->getDisplayLabel()],
        ['context' => 'Mollie payment instructions']
      ),
      '#format' => 'plain_text',
    ];

    return $instructions;
  }

}
