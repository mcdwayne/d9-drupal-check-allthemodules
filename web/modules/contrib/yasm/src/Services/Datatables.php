<?php

namespace Drupal\yasm\Services;

use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Implements Yasm Datatables helper.
 */
class Datatables implements DatatablesInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return '1.10.19';
  }

  /**
   * {@inheritdoc}
   */
  public function getLocale() {
    $name = $this->languageManager->getCurrentLanguage()->getName();
    $url = 'https://cdn.datatables.net/plug-ins/' . $this->getVersion() . '/i18n/' . $name . '.json';
    $response = $this->httpClient->get($url);

    return (200 == $response->getStatusCode()) ? $url : '';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(LanguageManagerInterface $language_manager, ClientInterface $http_client) {
    $this->languageManager = $language_manager;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('http_client')
    );
  }

}
