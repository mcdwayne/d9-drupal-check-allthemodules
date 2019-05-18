<?php

namespace Drupal\httpbin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\service_description\Loader\DescriptionLoader;
use GuzzleHttp\Client;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Controller.
 *
 * @package service_description
 */
class ServiceDescriptionController extends ControllerBase {

  /** @var \GuzzleHttp\Client */
  protected $client;

  /** @var \Drupal\service_description\Loader\DescriptionLoader */
  protected $descriptionLoader;

  public function __construct(Client $client, DescriptionLoader $description_loader) {
    $this->client = $client;
    $this->descriptionLoader = $description_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('service_description.loader')
    );
  }

  /**
   * Calls a service from description.
   */
  public function callTesting() {
    $description = $this->descriptionLoader->load('httpbin');
    $guzzleClient = new GuzzleClient($this->client, $description);

    $result = $guzzleClient->testing(['foo' => 'bar']);
    echo $result['args']['foo'];
    exit;
  }
}