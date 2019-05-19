<?php

namespace Drupal\simple_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\simple_integrations\ConnectionClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Return a new connection service from a container interface.
 *
 * Use this when setting up a new controller that needs a connection provided.
 * Extend this class to make sure you have all of the appropriate functionality
 * for connection to an external integration service.
 *
 * @see \Drupal\simple_integrations\ConnectionTestController
 */
class ConnectionController extends ControllerBase {

  /**
   * Custom Integration HTTP client.
   *
   * @var \Drupal\simple_integrations\ConnectionClient
   */
  protected $connection;

  /**
   * Put the connection where it's gotta go.
   *
   * @param \Drupal\simple_integrations\ConnectionClient $connection
   *   A connection client.
   */
  public function __construct(ConnectionClient $connection) {
    // Add the connection.
    $this->connection = $connection;
  }

  /**
   * Create a new client.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   A container interface.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('integration_http_client.client')
    );
  }

}
