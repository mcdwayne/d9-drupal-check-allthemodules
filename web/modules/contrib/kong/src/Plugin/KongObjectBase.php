<?php

namespace Drupal\kong\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Kong object plugins.
 */
abstract class KongObjectBase extends PluginBase implements KongObjectInterface, ContainerFactoryPluginInterface {

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  protected $endpoint;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientFactory $client_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client_factory->fromOptions([
      'base_uri' => $configuration['base_uri'],
      'headers' => [
        'Accept-Encoding' => 'gzip',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function add(array $data) {
    $response = $this->client->post($this->endpoint, ['json' => $data]);
    return json_decode($response->getBody(), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    assert(Uuid::isValid($id));

    try {
      $response = $this->client->get($this->endpoint . '/' . $id);
      return json_decode($response->getBody(), TRUE);
    }
    catch (ClientException $exception) {
      if ($exception->getCode() == '404') {
        return NULL;
      }

      throw $exception;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters = [], bool $count = FALSE) {
    $response = $this->client->get($this->endpoint, ['query' => $parameters]);
    $body = json_decode($response->getBody(), TRUE);

    return $count ? $body['total'] : $body['data'];
  }

  /**
   * {@inheritdoc}
   */
  public function update($id, array $data) {
    assert(Uuid::isValid($id));

    $response = $this->client->patch($this->endpoint . '/' . $id, ['json' => $data]);
    return json_decode($response->getBody(), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
    assert(Uuid::isValid($id));

    $this->client->delete($this->endpoint . '/' . $id);
  }

}
