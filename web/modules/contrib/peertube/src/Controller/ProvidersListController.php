<?php

namespace Drupal\peertube\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines ProvidersListController class.
 */
class ProvidersListController extends ControllerBase {

  /**
   * The PeerTube configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $peerTubeConfig;

  /**
   * Constructs a ProvidersListController object
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->peerTubeConfig = $config_factory->get('peertube.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Get the providers list with PeerTube ones.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Return the providers list in JSON format.
   */
  public function content() {
    // Get the default JSON.
    $data = $this->getDefaultProviders();
    // Add the defined PeerTube providers.
    $data = $this->addPeertubeProviders($data);
    
    // Return all the providers as JSON.
    $response = new Response();
    $response->setContent(json_encode($data));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  /**
   * Get the default providers list.
   *
   * @return array
   *   The default providers list.
   */
  public function getDefaultProviders() {
    $default_providers_url = $this->peerTubeConfig->get('oembed_providers_url');
    $jsonfile = file_get_contents($default_providers_url);
    return json_decode($jsonfile);
  }

  /**
   * Add PeerTube providers to the list.
   *
   * @param array $data
   *   The default providers list.
   *
   * @return array
   *   The comple providers list with PeerTube ones.
   */
  public function addPeertubeProviders(array $data) {
    $peertube_providers = $this->peerTubeConfig->get('peertube_instances');
    $peertube_providers_list = explode("\r\n", $peertube_providers);

    foreach($peertube_providers_list as $peertube_provider) {
      $data[] = $this->getPeertubeProviderArray($peertube_provider);
    }
    return $data;
  }

  /**
   * Function to get the provider representation in the JSON.
   *
   * @param string $provider_url
   *   The provider URL.
   *
   * @return array
   *   The provider representation.
   */
  public function getPeertubeProviderArray($provider_url) {
    $parse = parse_url($provider_url);
    return [
      'provider_name' => 'PeerTube',
      'provider_url' => $provider_url,
      'endpoints' => [
        0 => [
          'url' => $provider_url . '/services/oembed',
          'discovery' => TRUE,
        ],
      ],
    ];
  }

}
