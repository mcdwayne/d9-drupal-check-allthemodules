<?php

namespace Drupal\commerce_worldline\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_worldline\TransformOrder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SIPSPaymentRedirectForm.
 */
class SIPSPaymentRedirectForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();

    if ($payment->isNew()) {
      $payment->save();
    }

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $config = $payment_gateway_plugin->getConfiguration();

    $transformer = new TransformOrder();
    $paymentRequest = $transformer->toPaymentRequest(
      $config,
      $order,
      $form['#return_url'],
      $payment->id(),
      $config['sips_payment_method'] !== '' ? $config['sips_payment_method'] : NULL
    );

    try {
      $paymentRequest->validate();
    }
    catch (\Exception $e) {
      \Drupal::logger('commerce_worldline')
        ->warning('Payment request did not validate. Reason: ' . $e->getMessage());
      drupal_set_message('Payment request did not validate. Reason: ' . $e->getMessage());

      $cartUrl = Url::fromRoute('commerce_cart.page')
        ->toString(TRUE)
        ->getGeneratedUrl();
      $response = new LocalRedirectResponse($cartUrl);
      $response->send();
    }

    /* @var \GuzzleHttp\Client $client */
    $client = \Drupal::httpClient();
    $options['form_params'] = [
      'Data' => $paymentRequest->toParameterString(),
      'InterfaceVersion' => $config['sips_interface_version'],
      'Seal' => $paymentRequest->getShaSign(),
    ];

    $payment->set('sips_seal', $paymentRequest->getShaSign());
    $payment->save();
    $order->save();

    $response = $client->request('POST', $paymentRequest->getSipsUri(), $options);

    (new Response($response->getBody()))->send();
  }

}
