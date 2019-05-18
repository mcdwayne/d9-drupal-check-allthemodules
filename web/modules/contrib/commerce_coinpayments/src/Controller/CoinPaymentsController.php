<?php

namespace Drupal\commerce_coinpayments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_coinpayments\IPNCPHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for commerce_coinpayments module routes.
 */
class CoinPaymentsController extends ControllerBase {

  /**
   * The IPNCP handler.
   *
   * @var \Drupal\commerce_coinpayments\IPNCPHandlerInterface
   */
  protected $ipnCPHandler;

  /**
   * Constructs a \Drupal\commerce_coinpayments\Controller\CoinPaymentsController object.
   *
   * @param \Drupal\commerce_coinpayments\IPNCPHandlerInterface $ipn_cp_handler
   *   The IPN CPhandler.
   */
  public function __construct(IPNCPHandlerInterface $ipn_cp_handler) {
    $this->ipnCPHandler = $ipn_cp_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_coinpayments.ipn_cp_handler')
    );
  }

  /**
   * Process the IPN by calling IPNCPHandler service object.
   *
   * @return object
   *   A json object.
   */
  public function processIPN(Request $request) {

    // Get IPN request data and basic processing for the IPN request.
    $ipn_data = $this->ipnCPHandler->process($request);

    $response = new Response();
    $response->setContent(json_encode(['Status' => $ipn_data]));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

}
