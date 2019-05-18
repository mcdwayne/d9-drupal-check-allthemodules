<?php

namespace Drupal\api_ai_webhook\Plugin\ChatbotApiEntities\PushHandler;

use Drupal\chatbot_api_entities\Entity\EntityCollection;
use Drupal\chatbot_api_entities\Entity\EntityCollectionInterface;
use Drupal\chatbot_api_entities\Plugin\PushHandlerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Error;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a handler for pushing entities to api.ai.
 *
 * @PushHandler(
 *   id = "api_ai_webhook",
 *   label = @Translation("API AI entities endpoint")
 * )
 */
class ApiAiPushHandler extends PushHandlerBase {

  const ENDPOINT_ROOT = 'https://api.api.ai/v1/entities/';

  /**
   * API version we are ready to handle.
   *
   * @see https://api.ai/docs/reference/agent/#versioning
   */
  const SUPPORTED_VERSION = '20170901';

  /**
   * Site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ApiAiPushHandler object.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   HTTP client.
   * @param \Drupal\Core\Site\Settings $settings
   *   Settings.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $httpClient, Settings $settings, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $httpClient);
    $this->settings = $settings;
    $this->logger = $logger;
    $this->configuration += [
      'settings' => [
        'remote_name' => '',
        'remote_id' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('settings'),
      $container->get('logger.factory')->get('api_ai_webhook')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function pushEntities(array $entities, EntityCollection $entityCollection) {
    $entries = $this->formatEntries($entities, $entityCollection);
    $entityId = $this->configuration['settings']['remote_id'];
    $loggerArgs = [
      '@name' => $entityCollection->label(),
    ];
    try {
      $body = [
        'id' => $entityId,
        'name' => $this->configuration['settings']['remote_name'],
        'entries' => $entries,
      ];
      $this->httpClient->request('PUT', self::ENDPOINT_ROOT . $entityId, [
        'query' => $this->buildQuery(),
        'headers' => $this->buildHeaders(),
        'body' => Json::encode($body),
      ]);
      $this->logger->info('Synchronized Chatbot API entity collection @name to API.ai', $loggerArgs);
    }
    catch (ClientException $e) {
      $this->logger->error('Error synchronizing Chatbot API entity collection @name to API.ai - %type: @message in %function (line %line of %file)', Error::decodeException($e) + $loggerArgs);
    }
    return $this;
  }

  /**
   * Build HTTP request URL query parameters.
   *
   * @return array
   *   Array of URL query parameters.
   */
  protected function buildQuery() {
    return ['v' => self::SUPPORTED_VERSION];
  }

  /**
   * Build HTTP request headers.
   *
   * @return array
   *   Array of headers.
   */
  protected function buildHeaders() {
    if (!$token = $this->settings->get('api_ai_webhook_developer_token', FALSE)) {
      throw new \UnexpectedValueException("In order to use the api.ai remote entity push, you need to add your developer token to settings.php. E.g. \$settings['api_ai_webhook_developer_token'] = 'your token here'. Refer the settings page for your agent in the api.ai console to find your developer token.");
    }
    return [
      'Authorization' => 'Bearer ' . $token,
      'Content-Type' => 'application/json; charset=utf-8',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfiguration(EntityCollectionInterface $entityCollection, array $configuration) {
    if (!empty($configuration['settings']['remote_id'])) {
      return $configuration;
    }
    if ($entityCollection->isNew()) {
      $loggerArgs = [
        '@name' => $entityCollection->label(),
      ];
      $existing = $this->getExistingEntityMap($loggerArgs);
      $remote_name = $this->configuration['settings']['remote_name'];
      if (isset($existing[$remote_name])) {
        $configuration['settings']['remote_id'] = $existing[$remote_name];
        return parent::saveConfiguration($entityCollection, $configuration);
      }
      // New entity, we need to push.
      try {
        $body = [
          'name' => $remote_name,
          'entries' => [],
        ];
        $response = $this->httpClient->request('POST', self::ENDPOINT_ROOT, [
          'query' => $this->buildQuery(),
          'headers' => $this->buildHeaders(),
          'body' => Json::encode($body),
        ]);
        $this->logger->info('Generated remote ID for Chatbot API entity collection @name on API.ai', $loggerArgs);
        $decoded = Json::decode((string) $response->getBody());
        $configuration['settings']['remote_id'] = $decoded['id'];
      }
      catch (RequestException $e) {
        $this->logger->error('Error generate remote ID for Chatbot API entity collection @name on API.ai - %type: @message in %function (line %line of %file)', Error::decodeException($e) + $loggerArgs);
        throw new EntityStorageException(sprintf('Unable to generate a remote ID for entity collection %s.', $entityCollection->label()));
      }
    }
    return parent::saveConfiguration($entityCollection, $configuration);
  }

  /**
   * Gets map of existing entity IDs keyed by name.
   *
   * @param array $loggerArgs
   *   Array of variables replacements for logger.
   *
   * @return array
   *   Array with entity name as key and ID as value.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If cannot communicate with the remote end point.
   */
  protected function getExistingEntityMap(array $loggerArgs) {
    try {
      $response = $this->httpClient->request('GET', self::ENDPOINT_ROOT, [
        'query' => $this->buildQuery(),
        'headers' => $this->buildHeaders(),
      ]);
      $decoded = json_decode((string) $response->getBody(), TRUE);
      return array_combine(array_column($decoded, 'name'), array_column($decoded, 'id'));
    }
    catch (RequestException $e) {
      $this->logger->error('Error getting list of existing remote IDs for Chatbot API entity collection @name on API.ai - %type: @message in %function (line %line of %file)', Error::decodeException($e) + $loggerArgs);
      throw new EntityStorageException(sprintf('Error getting list of existing remote IDs for Chatbot API entity collection %s on API.ai', $loggerArgs['@name']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(EntityCollectionInterface $entityCollection, array $form, FormStateInterface $form_state) {
    return [
      'remote_name' => [
        '#type' => 'textfield',
        '#title' => new TranslatableMarkup('Remote name'),
        '#description' => new TranslatableMarkup('Give the collection a name on API.ai'),
        '#default_value' => $this->configuration['settings']['remote_name'],
      ],
      'remote_id' => [
        '#type' => 'value',
        '#value' => $this->configuration['settings']['remote_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(EntityCollectionInterface $entityCollection, array $form, FormStateInterface $form_state) {
    if (!$form_state->getValue([
      'push_handler_configuration',
      $this->pluginId,
      'settings',
      'remote_name',
    ])) {
      $form_state->setError($form['push_handlers']['settings'][$this->pluginId], new TranslatableMarkup('Remote name is required'));
    }
  }

}
