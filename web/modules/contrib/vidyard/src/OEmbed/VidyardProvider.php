<?php

namespace Drupal\vidyard\OEmbed;

use Drupal\media\OEmbed\ProviderRepository;
use Drupal\media\OEmbed\ProviderRepositoryInterface;
use Drupal\media\OEmbed\Provider;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class for customizing the oEmbed provider repository.
 *
 * Decorates the Media oEmbed ProviderRepository with getAll() modified to add
 * Vidyard as an available oEmbed provider.
 *
 * @package Drupal\vidyard\OEmbed
 */
class VidyardProvider extends ProviderRepository {

  /**
   * @inheritdoc
   */
  public function __construct(ProviderRepositoryInterface $inner_service, ClientInterface $http_client, ConfigFactoryInterface $config_factory, TimeInterface $time, CacheBackendInterface $cache_backend = NULL, $max_age = 604800) {
    $this->innerService = $inner_service;
    parent::__construct($http_client, $config_factory, $time, $cache_backend, $max_age);
  }

  /**
   * Gets the list of available oEmbed providers and adds Vidyard as a provider.
   *
   * @see \Drupal\media\OEmbed\ProviderRepository::getAll()
   */
  public function getAll() {

    $keyed_providers = $this->innerService->getAll();

    $provider_definition = [
      'provider_name' => 'Vidyard',
      'provider_url' => 'https://www.vidyard.com/',
      'endpoints' => [
        [
          'url' => 'https://api.vidyard.com/dashboard/v1/oembed.{format}',
          'schemes' => [
            'http(s)://embed.vidyard.com/share/*',
            'http(s)://play.vidyard.com/*',
            'http(s)://*.hubs.vidyard.com/watch/*',
          ],
          'formats' => ['json'],
        ],
      ],
    ];

    $provider['Vidyard'] = new Provider($provider_definition['provider_name'], $provider_definition['provider_url'], $provider_definition['endpoints']);

    return $provider + $keyed_providers;

  }

}
