<?php

namespace Drupal\daemons_example\Plugin\Daemons;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\State\State;
use Drupal\daemons\DaemonPluginBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Example daemon.
 *
 * @Daemon(
 *   id="example_daemon",
 *   label="Example daemon",
 *   periodicTimer="60",
 * )
 */
class ExampleDaemon extends DaemonPluginBase {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a new LittersUpdateQueueDaemon object.
   *
   * @param array $configuration
   *   Configuration of Daemon.
   * @param string $plugin_id
   *   Plugin id.
   * @param object $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\State\State $state
   *   The state key-value store service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, State $state, ClientInterface $http_client, MailManagerInterface $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $state);
    $this->state = $state;
    $this->httpClient = $http_client;
    $this->mailManager = $mail_manager;
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('http_client'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($loop) {
    try {
      $response = $this->httpClient->get('http://loripsum.net/api/1/plaintext');
    }
    catch (RequestException $e) {
      throw new \Exception('Error message: ' . $e->getMessage());
    }
    $params['message'] = $response->getBody();
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $this
      ->mailManager
      ->mail('daemons_example', 'daemons', NULL, $langcode, $params, NULL, TRUE);
  }

}
