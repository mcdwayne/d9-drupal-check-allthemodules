<?php

namespace Drupal\commerce_iats\Rest;

use Drupal\commerce_iats\Exception\GatewayException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Gateway.
 */
class Gateway implements GatewayInterface {

  protected $baseUrl = 'https://secure.1stpaygateway.net/secure/RestGW/Gateway/Transaction';

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * API merchant key.
   *
   * @var string
   */
  protected $merchantKey;

  /**
   * API processor ID.
   *
   * @var string
   */
  protected $processorId;

  /**
   * The current HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Gateway constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   HTTP client.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   */
  public function __construct(ClientInterface $client, Request $request) {
    $this->client = $client;
    $this->request = $request;
  }

  /**
   * Adds authorization data to request data.
   *
   * @param array $data
   *   Request data.
   *
   * @return array
   *   Request data with authorization data set.
   */
  protected function addAuthParams(array $data) {
    $data['merchantKey'] = $this->merchantKey;
    $data['processorId'] = $this->processorId;
    $data['ipAddress'] = $this->request->getClientIp();
    return $data;
  }

  /**
   * Sets authorization data into the Gateway service.
   *
   * @param string $merchantKey
   *   The merchant ID.
   * @param string $processorId
   *   The processor ID.
   *
   * @return $this
   */
  public function setAuth($merchantKey, $processorId) {
    $this->merchantKey = $merchantKey;
    $this->processorId = $processorId;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function post($function, array $data = []) {
    $data = $this->addAuthParams($data);

    $url = rtrim($this->baseUrl, '/') . '/' . $function;
    try {
      $response = $this->client->request('POST', $url, [
        RequestOptions::JSON => $data,
      ]);
    }
    catch (BadResponseException $e) {
      throw GatewayException::createFromBadResponse($e);
    }

    return \GuzzleHttp\json_decode($response->getBody());
  }

  /**
   * {@inheritdoc}
   */
  public function achGetCategories() {
    return $this->post('AchGetCategories')->data;
  }

  /**
   * {@inheritdoc}
   */
  public function creditCardCredit(array $data) {
    return $this->post('Credit', $data)->data;
  }

  /**
   * {@inheritdoc}
   */
  public function creditCardSettle(array $data) {
    return $this->post('Settle', $data)->data;
  }

  /**
   * {@inheritdoc}
   */
  public function creditCardVoid(array $data) {
    return $this->post('Void', $data);
  }

  /**
   * {@inheritdoc}
   */
  public function firstPayAchCredit($vaultKey, $id, array $data) {
    $data['vaultKey'] = $vaultKey;
    $data['vaultId'] = $id;
    return $this->post('AchCreditUsingVault', $data)->data;
  }

  /**
   * {@inheritdoc}
   */
  public function firstPayAchDebit($vaultKey, $id, array $data) {
    $data['vaultKey'] = $vaultKey;
    $data['vaultId'] = $id;
    return $this->post('AchDebitUsingVault', $data)->data;
  }

  /**
   * {@inheritdoc}
   */
  public function firstPayCcAuth($vaultKey, $id, array $data) {
    $data['vaultKey'] = $vaultKey;
    $data['vaultId'] = $id;
    return $this->post('AuthUsingVault', $data)->data;
  }

  /**
   * {@inheritdoc}
   */
  public function firstPayCcSale($vaultKey, $id, array $data) {
    $data['vaultKey'] = $vaultKey;
    $data['vaultId'] = $id;
    return $this->post('SaleUsingVault', $data)->data;
  }

  /**
   * {@inheritdoc}
   */
  public function vaultAchCreate($vaultKey, array $data) {
    $data['vaultKey'] = $vaultKey;
    return $this->post('VaultCreateAchRecord', $data)->data;
  }

  /**
   * {@inheritdoc}
   */
  public function vaultAchDelete($vaultKey, $id) {
    $data['vaultKey'] = $vaultKey;
    $data['id'] = $id;
    $this->post('VaultDeleteAchRecord', $data);
  }

  /**
   * {@inheritdoc}
   */
  public function vaultAchLoad($vaultKey, $id) {
    $data = $this->vaultAchQuery($vaultKey);
    $cards = array_filter($data->bankingRecords, function ($item) use ($id) {
      return $item->id == $id;
    });
    return reset($cards);

  }

  /**
   * {@inheritdoc}
   */
  public function vaultAchQuery($vaultKey, array $data = []) {
    $data['queryVaultKey'] = $vaultKey;
    return $this->post('VaultQueryAchRecord', $data)->data;
  }

  /**
   * {@inheritdoc}
   */
  public function vaultCcCreate($vaultKey, array $data) {
    $data['vaultKey'] = $vaultKey;
    return $this->post('VaultCreateCCRecord', $data)->data;
  }

  /**
   * {@inheritdoc}
   */
  public function vaultCcDelete($vaultKey, $id) {
    $data['vaultKey'] = $vaultKey;
    $data['id'] = $id;
    $this->post('VaultDeleteCCRecord', $data);
  }

  /**
   * {@inheritdoc}
   */
  public function vaultCcLoad($vaultKey, $id) {
    $data = $this->vaultCcQuery($vaultKey);
    $cards = array_filter($data->creditCardRecords, function ($item) use ($id) {
      return $item->id == $id;
    });
    return reset($cards);
  }

  /**
   * {@inheritdoc}
   */
  public function vaultCcQuery($vaultKey, array $data = []) {
    $data['queryVaultKey'] = $vaultKey;
    return $this->post('VaultQueryCCRecord', $data)->data;
  }

  /**
   * {@inheritdoc}
   */
  public function vaultQuery(array $data = []) {
    $vaults = $this->post('VaultQueryVault', $data)->data->VaultContainers;
    $vaultIds = array_map(function ($item) {
      return $item->vaultId;
    }, $vaults);
    return array_combine($vaultIds, $vaults);
  }

}
