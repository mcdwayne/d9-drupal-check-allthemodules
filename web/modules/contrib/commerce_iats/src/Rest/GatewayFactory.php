<?php

namespace Drupal\commerce_iats\Rest;

use Drupal\Core\Http\ClientFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class GatewayFactory.
 *
 * Instantiates REST gateway instances.
 */
class GatewayFactory {

  /**
   * HTTP client factory service.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * GatewayFactory constructor.
   *
   * @param \Drupal\Core\Http\ClientFactory $clientFactory
   *   HTTP client factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(ClientFactory $clientFactory, RequestStack $requestStack) {
    $this->clientFactory = $clientFactory;
    $this->requestStack = $requestStack;
  }

  /**
   * Get a rest API gateway.
   *
   * @param string $merchantKey
   *   The merchant ID.
   * @param string $processorId
   *   The processor ID.
   *
   * @return \Drupal\commerce_iats\Rest\Gateway
   *   The rest API gateway.
   */
  public function getGateway($merchantKey, $processorId) {
    $gateway = new Gateway($this->clientFactory->fromOptions(), $this->requestStack->getCurrentRequest());
    return $gateway->setAuth($merchantKey, $processorId);
  }

}
