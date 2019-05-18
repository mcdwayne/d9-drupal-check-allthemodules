<?php

namespace Drupal\commerce_payway\Client;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payway\Exception\PayWayClientException;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;

/**
 * Class PayWayRestApiClient.
 *
 * @package Drupal\commerce_payway\Client
 */
class PayWayRestApiClient implements PayWayRestApiClientInterface {

  /**
   * The REST API Client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $client;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  private $uuidService;

  /**
   * The response from the REST API.
   *
   * @var \Psr\Http\Message\ResponseInterface
   */
  private $response;

  /**
   * The default request method.
   */
  const METHOD = 'POST';

  /**
   * The default currency.
   */
  const CURRENCY = 'aud';

  /**
   * The request transaction type.
   */
  const TRANSACTION_TYPE_PAYMENT = 'payment';

  /**
   * PayWayRestApiClient constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   Guzzle client.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   Uuid service.
   */
  public function __construct(
    ClientInterface $client,
    UuidInterface $uuid_service
  ) {
    $this->client = $client;
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public function doRequest(PaymentInterface $payment, array $configuration) {
    $payment_method = $payment->getPaymentMethod();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    try {
      // Prepare the one-time payment.
      $owner = $payment_method->getOwner();
      $customerNumber = 'anonymous';
      if ($owner && !$owner->isAnonymous()) {
        $customerNumber = $owner->get('uid')->first()->value;
      }

      $this->response = $this->client->request(
        static::METHOD, $configuration['api_url'], [
          'form_params' => [
            'singleUseTokenId' => $payment_method->getRemoteId(),
            'customerNumber' => $customerNumber,
            'transactionType' => static::TRANSACTION_TYPE_PAYMENT,
            'principalAmount' => round($payment->getAmount()->getNumber(), 2),
            'currency' => static::CURRENCY,
            'orderNumber' => $order->id(),
            'merchantId' => $configuration['merchant_id'],
          ],
          'headers' => [
            'Authorization' => 'Basic ' . base64_encode($this->getSecretKey($configuration)),
            'Idempotency-Key' => $this->uuidService->generate(),
          ],
        ]
      );
    }
    catch (GuzzleException $e) {
      throw new PayWayClientException('Request failed due to API.');
    }
    catch (InvalidArgumentException $e) {
      throw new PayWayClientException('Request failed due to invalid user.');
    }
    catch (MissingDataException $e) {
      throw new PayWayClientException('Request failed due to missing data.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    if ($this->response !== NULL) {
      return (string) $this->response->getBody();
    }
    return '';
  }

  /**
   * Get the secret Key.
   *
   * @param array $configuration
   *    The plugin configuration.
   *
   * @return string
   *    The secret key.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getSecretKey($configuration) {
    switch ($configuration['mode']) {
      case 'test':
        $secretKey = $configuration['secret_key_test'];
        break;

      case 'live':
        $secretKey = $configuration['secret_key'];
        break;

      default:
        throw new MissingDataException('The private key is empty.');
    }
    return $secretKey;
  }

}
