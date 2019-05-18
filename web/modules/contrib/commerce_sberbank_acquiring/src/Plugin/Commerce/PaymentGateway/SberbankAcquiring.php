<?php

namespace Drupal\commerce_sberbank_acquiring\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Voronkovich\SberbankAcquiring\Client as SberbankClient;
use Voronkovich\SberbankAcquiring\HttpClient\GuzzleAdapter as SberbankGuzzleAdapter;
use Voronkovich\SberbankAcquiring\OrderStatus as SberbankOrderStatus;

/**
 * Provides the Sberbank Acquiring payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "sberbank_acquiring",
 *   label = @Translation("Sberbank Acquiring"),
 *   display_label = @Translation("Sberbank"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_sberbank_acquiring\PluginForm\OffsiteRedirect\SberbankAcquiringForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "maestro", "mastercard", "visa", "mir",
 *   },
 * )
 */
class SberbankAcquiring extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'username' => '',
        'password' => '',
        'order_id_prefix' => '',
        'order_id_suffix' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Username"),
      '#required' => TRUE,
      '#default_value' => $this->configuration['username'],
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t("Password"),
      '#description' => $this->t("Password stored in database. To change it, enter new password, or leave field empty and password won't change."),
      '#default_value' => $this->configuration['password'],
    ];

    $form['prefix_and_suffix'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Order ID prefix and suffix'),
    ];

    $description = '<p>' . $this->t("By default you don't need to change those settings and they must be leaved as empty strings.") . '</p>';
    $description .= '<p>' . $this->t("But if you have issues with same order ID, because of using API before, your only way is to change order ID sent to Sberbank. The simple way is to add prefix or\and suffix, to make ID's unique.") . '</p>';
    $description .= '<p>' . $this->t("This only affects order ID's name at sberbank acquiring, the commerce order id will be the same.") . '</p>';
    $description .= '<p><em>' . $this->t("They also can be used for testing purposes when you have several development environments and their order ID is intersect.") . '</em></p>';
    $form['prefix_and_suffix']['description'] = [
      '#type' => 'markup',
      '#markup' => $description,
    ];

    $form['prefix_and_suffix']['order_id_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Prefix"),
      '#description' => $this->t("E.g. prefix 'site-2018-' with order id 17 will be send as 'site-2018-17'."),
      '#default_value' => $this->configuration['order_id_prefix'],
    ];

    $form['prefix_and_suffix']['order_id_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Suffix"),
      '#description' => $this->t("E.g. suffix '-site-2018' with order id 17 will be send as '17-site-2018'."),
      '#default_value' => $this->configuration['order_id_suffix'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if ($values['password'] == '' && $this->configuration['password'] == '') {
      $form_state->setError($form['password'], $this->t("Password field is required."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Parent method will reset configuration array and further condition will
    // fail. So we temporary store old password before configuration was erased.
    $current_password = $this->configuration['password'];
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['username'] = $values['username'];
      $this->configuration['order_id_prefix'] = $values['prefix_and_suffix']['order_id_prefix'];
      $this->configuration['order_id_suffix'] = $values['prefix_and_suffix']['order_id_suffix'];
      // Handle password saving.
      if ($values['password'] != '') {
        $this->configuration['password'] = $values['password'];
      }
      else {
        $this->configuration['password'] = $current_password;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    // Get Sberbank orderId.
    $remote_id = $request->query->get('orderId');

    // Set REST API url for test or live modes.
    switch ($this->getMode()) {
      default:
      case 'test':
        $api_uri = SberbankClient::API_URI_TEST;
        break;

      case 'live':
        $api_uri = SberbankClient::API_URI;
        break;
    }

    $client = new SberbankClient([
      'userName' => $this->configuration['username'],
      'password' => $this->configuration['password'],
      'apiUri' => $api_uri,
      'httpClient' => new SberbankGuzzleAdapter(\Drupal::httpClient()),
    ]);

    $order_status = $client->getOrderStatusExtended($remote_id);

    $payment = $payment_storage->loadByRemoteId($remote_id);

    switch ($order_status['orderStatus']) {
      case SberbankOrderStatus::DEPOSITED:
        $payment->setState('completed');
        $payment->setAmount($order->getTotalPrice());
        $payment->setRemoteState($order_status['paymentAmountInfo']['paymentState']);
        $payment->setCompletedTime(time());
        $payment->save();
        break;

      default:
      case SberbankOrderStatus::DECLINED:
        $payment->setState('authorization_voided');
        $payment->save();
        throw new PaymentGatewayException('Payment failed!');
    }
  }

}
