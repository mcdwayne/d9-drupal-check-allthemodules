<?php

namespace Drupal\auto_alter;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Component\Utility\Xss;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class to connect to Azure Cognitive Service.
 */
class AzureVision {

  /**
   * The file system service.
   *
   * @var Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The httpClient.
   *
   * @var GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Class constructor.
   */
  public function __construct(FileSystemInterface $file_system, ClientInterface $http_client, ConfigFactory $configFactory, LoggerChannelFactory $loggerFactory) {
    $this->fileSystem = $file_system;
    $this->httpClient = $http_client;
    $this->config = $configFactory->get('auto_alter.settings');
    $this->loggerFactory = $loggerFactory->get('auto_alter');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('http_client')
    );
  }

  /**
   * Check if setup is complete.
   */
  public function checksetup() {
    $endpoint = Xss::filter($this->config->get('endpoint'));
    $api_key = Xss::filter($this->config->get('api_key'));
    if (empty($api_key) || empty($endpoint)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Get the correct URI of the image.
   */
  public function geturi(File $file) {
    $filesize = $file->getSize();
    $uri = $file->get('uri')->value;
    if ($filesize > 1048576) {
      $style = ImageStyle::load('auto_alter_help');
      $original_uri = $uri;
      $uri = $style->buildUri($original_uri);
      $style->createDerivative($original_uri, $uri);
    }
    return $uri;
  }

  /**
   * Get the description of the image.
   */
  public function getdescription(string $uri_or_relpath, $endpoint = FALSE, $api_key = FALSE) {
    $path = $this->fileSystem->realpath($uri_or_relpath);
    $client = $this->httpClient;
    try {
      $endpoint = $endpoint ? $endpoint : $this->config->get('endpoint');
      $api_key = $api_key ? $api_key : $this->config->get('api_key');
      $request = $client->post(Xss::filter($endpoint), [
        'headers' => [
          'Ocp-Apim-Subscription-Key' => Xss::filter($api_key),
        ],
        'multipart' => [
          [
            'name' => 'file',
            'contents' => fopen($path, "r"),
          ],
        ],
      ]);
    }
    catch (RequestException $e) {
      $this->loggerFactory->error(
        "Azure Cognitive Services error code @code: @message",
        [
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]
      );
      if ($e->hasResponse()) {
        $request = $e->getResponse();
      }
      else {
        $request = FALSE;
      }
    }
    return $request;
  }

}
