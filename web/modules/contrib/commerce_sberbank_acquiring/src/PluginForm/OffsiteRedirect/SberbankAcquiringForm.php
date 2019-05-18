<?php

namespace Drupal\commerce_sberbank_acquiring\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Voronkovich\SberbankAcquiring\Client as SberbankClient;
use Voronkovich\SberbankAcquiring\Exception\ActionException;
use Voronkovich\SberbankAcquiring\HttpClient\GuzzleAdapter as SberbankGuzzleAdapter;

/**
 * Order registration and redirection to payment URL.
 */
class SberbankAcquiringForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    // Force save payment to set id for it. It used to generate 'order_id' for
    // sberbank.
    $payment->setState('new');
    $payment->save();

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $configs = $payment_gateway_plugin->getConfiguration();
    // Get username and password for payment method.
    $username = $configs['username'];
    $password = $configs['password'];
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = \Drupal::entityTypeManager()
      ->getStorage('commerce_currency')
      ->load($payment->getAmount()->getCurrencyCode());

    // Set REST API url for test or live modes.
    switch ($this->plugin->getMode()) {
      default:
      case 'test':
        $api_uri = SberbankClient::API_URI_TEST;
        break;

      case 'live':
        $api_uri = SberbankClient::API_URI;
        break;
    }

    // Prepare client to be executed.
    $client = new SberbankClient([
      'userName' => $username,
      'password' => $password,
      'language' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      // ISO 4217 currency code.
      'currency' => $currency->getNumericCode(),
      'apiUri' => $api_uri,
      'httpClient' => new SberbankGuzzleAdapter(\Drupal::httpClient()),
    ]);

    // Set additional params to order.
    // We use Payment ID instead of actual order id, because sberbank allows to
    // register payment with same 'order_id' once. So if something wrong
    // happens, user can't pay for this order again using this payment method.
    // So Payment ID is more reliable, because every try it will have new ID,
    // and we can get order by payment_id.
    $order_id = $configs['order_id_prefix'] . $payment->id() . $configs['order_id_suffix'];
    $order_amount = (int) ($payment->getAmount()->getNumber() * 100);

    $params = [
      'failUrl' => $form['#cancel_url'],
    ];

    $context = [
      'payment' => $payment,
    ];
    \Drupal::moduleHandler()
      ->alter('commerce_sberbank_acquiring_register_order', $params, $context);

    // Execute request to Sberbank.
    try {
      $result = $client->registerOrder($order_id, $order_amount, $form['#return_url'], $params);

      $payment->setAuthorizedTime(time());
      $payment->setRemoteId($result['orderId']);
      $payment->setState('authorization');
      $payment->save();
    }
    catch (ActionException $exception) {
      // If something goes wrong, we stop payment and show error for it.
      \Drupal::logger('commerce_sberbank_acquiring')
        ->error("Payment for order #@order_id is throws an error. Message: @message", [
          '@order_id' => $payment->getOrderId(),
          '@message' => $exception->getMessage(),
        ]);

      // Mark payment is failed.
      $payment->setState('authorization_voided');
      $payment->save();

      throw new PaymentGatewayException();
    }

    $payment_form_url = $result['formUrl'];

    return $this->buildRedirectForm($form, $form_state, $payment_form_url, [], self::REDIRECT_GET);
  }

}
