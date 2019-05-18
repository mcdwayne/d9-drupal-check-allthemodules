<?php

namespace Drupal\cmlexchange\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cmlexchange\Service\ProtocolInterface;
use Drupal\cmlexchange\Service\DebugServiceInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for /cmlexchange Page.
 */
class CmlExchange extends ControllerBase {

  protected $cmlexchangeProtocol;
  protected $cmlexchangeDebug;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cmlexchange.protocol'),
      $container->get('cmlexchange.debug')
    );
  }

  /**
   * CmlExchange constructor.
   *
   * @param \Drupal\cmlexchange\Service\ProtocolInterface $protocol
   *   Protocol service.
   * @param \Drupal\cmlexchange\Service\DebugServiceInterface $debug
   *   Debug service.
   */
  public function __construct(
    ProtocolInterface $protocol,
    DebugServiceInterface $debug
  ) {
    $this->cmlexchangeProtocol = $protocol;
    $this->debugService = $debug;
  }

  /**
   * Main.
   */
  public function exchange() {
    // Init.
    $result = $this->cmlexchangeProtocol->init();
    $this->debugService->debug(__CLASS__, "Response: " . $result);
    // Admin debug.
    if (\Drupal::currentUser()->id() === '1') {
      return ['#markup' => "<pre>{$result}</pre>"];
    }
    // Response.
    $response = new Response($result);
    $response->headers->set('Content-Type', 'text/plain');
    return $response;

  }

}
