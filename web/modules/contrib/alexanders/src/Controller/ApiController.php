<?php

namespace Drupal\alexanders\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\alexanders\Entity\AlexandersOrder;

/**
 * Handles incoming API requests from Alexanders.
 *
 * @package Drupal\alexanders\Controller
 */
class ApiController extends ControllerBase {

  /**
   * Updates 'due date' parameter of Alexanders order.
   *
   * @param \Drupal\alexanders\Entity\AlexandersOrder $order
   *   Order ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Incoming request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns status code based on result.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function orderPrintingUpdate(AlexandersOrder $order, Request $request) {
    $response = new Response();

    $json = json_decode($request->getContent());
    $headers = $request->headers;
    if ($json && $json->dueDate) {
      $due_date = strtotime($json->dueDate);
      $auth = $this->apiKeyValidation($headers->get('X-API-KEY'));
      // If in a sandbox, don't save the order, or pass 401 status code.
      switch ($auth) {
        case 'sandbox':
          $order->setDue($due_date);
          break;

        case 'real':
          // Placeholder api key value, replace with randomly generated value.
          $order->setDue($due_date);
          $order->save();
          break;

        default:
          $response->setStatusCode(401);
          break;
      }
    }
    return $response;
  }

  /**
   * Updates order shipment status.
   *
   * @param \Drupal\alexanders\Entity\AlexandersOrder $order
   *   Order ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Incoming request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns status code based on result.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function orderShippedUpdate(AlexandersOrder $order, Request $request) {
    $response = new Response();
    /** @var \Drupal\alexanders\Entity\AlexandersShipment $shipment */
    $shipment = $order->getShipment();

    $json = json_decode($request->getContent());
    $headers = $request->headers;
    if ($json && $json->dueDate) {
      $shipdate = strtotime($json->dueDate);
      $auth = $this->apiKeyValidation($headers->get('X-API-KEY'));
      // If in a sandbox, don't save the order, or pass 401 status code.
      switch ($auth) {
        case 'sandbox':
          break;

        case 'real':
          $shipment->setTimestamp($shipdate);
          $shipment->setCost($json->cost);
          $shipment->setTracking($json->trackingNumber);
          $shipment->save();
          break;

        default:
          $response->setStatusCode(401);
          break;
      }
    }

    return $response;
  }

  /**
   * Logs an error associated with the item for manual review.
   *
   * @param \Drupal\alexanders\Entity\AlexandersOrder $order
   *   Order ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Incoming request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns status code based on result.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function orderErrorUpdate(AlexandersOrder $order, Request $request) {
    $response = new Response();

    $json = json_decode($request->getContent());
    $headers = $request->headers;
    if ($json && $json->message) {
      $auth = $this->apiKeyValidation($headers->get('X-API-KEY'));
      // If in a sandbox, don't save the order, or pass 401 status code.
      switch ($auth) {
        case 'sandbox':
          break;

        case 'real':
          // Placeholder api key value, replace with randomly generated value.
          $this->getLogger('alexanders_api')
            ->error('Alexanders API sent error processing order @orderid: @message', [
              '@orderid' => $order_id,
              '@message' => $json->message,
            ]);
          break;

        default:
          $response->setStatusCode(401);
          break;
      }
    }
    else {
      $response->setStatusCode(400);
    }
    return $response;
  }

  /**
   * Validate API key.
   *
   * @param string $api_key
   *   API key passed to the API.
   *
   * @return bool|string
   *   Type of key being passed,
   */
  private function apiKeyValidation($api_key) {
    if ($api_key === $this->config('alexanders.settings')->get('real_api_key')) {
      return 'real';
    }
    if ($api_key === $this->config('alexanders.settings')->get('sandbox_api_key')) {
      return 'sandbox';
    }

    return FALSE;
  }

}
