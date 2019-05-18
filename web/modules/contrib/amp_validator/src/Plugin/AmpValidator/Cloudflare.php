<?php

namespace Drupal\amp_validator\Plugin\AmpValidator;

use Drupal\amp_validator\Annotation\AmpValidatorPlugin;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\TranslationInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\amp_validator\Plugin\AmpValidatorPluginBase;

/**
 * Cloudflare AmpValidator plugin.
 *
 * @AmpValidatorPlugin(
 *  id = "cloudflare",
 *  label = @Translation("Cloudflare"),
 *  description=@Translation("AMP Validator provided by Cloudflare")
 * )
 *
 * @package Drupal\amp_validator\Plugin\AmpValidator
 */
class Cloudflare extends AmpValidatorPluginBase {

  /**
   * The HTTP client to fetch the files with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   *
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation, ConfigFactory $config_factory, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $string_translation, $config_factory);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($type = 'url') {
    if (!empty($this->data)) {

      $method = 'GET';
      $options = [];
      switch ($type) {
        case 'file':
          $url = 'https://amp.cloudflare.com/q/';
          $method = 'POST';
          $options = [
            'headers' => [
              'Content-Type' => 'text/html; charset=UTF-8',
            ],
            'body' => fopen($this->data, 'r'),
          ];
          break;

        default:
          // TODO: Exists a better (Drupal) way to do this?
          $ampUrl = preg_replace("(^https?://)", "", $this->data->toString());
          $url = 'https://amp.cloudflare.com/q/' . $ampUrl;
      }

      try {
        $response = $this->httpClient->request($method, $url, $options);
        $code = $response->getStatusCode();
        if ($code == 200) {
          $validatorResult = $response->getBody()->getContents();

          $validAmp = Json::decode($validatorResult);
          if ($validAmp['valid']) {
            $this->valid = TRUE;
          }

          if (!$this->valid && !empty($validAmp['errors'])) {
            foreach ($validAmp['errors'] as $error) {
              $this->errors[] = [
                'code' => $error['code'],
                'info' => $error['error'],
                'line' => $error['line'],
                'col' => $error['col'],
                'help' => isset($error['help']) ? $error['help'] : '',
              ];
            }
          }
        }
      }
      catch (GuzzleException $e) {
        watchdog_exception('amp_validator', $e);
      }
    }
  }

}
