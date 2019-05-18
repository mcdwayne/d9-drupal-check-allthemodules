<?php

namespace Drupal\external_entities\Plugin\ExternalEntities\StorageClient;

use Drupal\external_entities\StorageClient\ExternalEntityStorageClientBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\external_entities\Plugin\PluginFormTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\external_entities\ResponseDecoder\ResponseDecoderFactoryInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\external_entities\ExternalEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * External entities storage client based on a REST API.
 *
 * @ExternalEntityStorageClient(
 *   id = "rest",
 *   label = @Translation("REST"),
 *   description = @Translation("Retrieves external entities from a REST API.")
 * )
 */
class Rest extends ExternalEntityStorageClientBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The HTTP client to fetch the files with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Constructs a Rest object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\external_entities\ResponseDecoder\ResponseDecoderFactoryInterface $response_decoder_factory
   *   The response decoder factory service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation, ResponseDecoderFactoryInterface $response_decoder_factory, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $string_translation, $response_decoder_factory);
    $this->httpClient = $http_client;
    $this->logger = \Drupal::logger('external_entities');
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
      $container->get('external_entities.response_decoder_factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'endpoint' => NULL,
      'response_format' => NULL,
      'pager' => [
        'default_limit' => 50,
        'page_parameter' => NULL,
        'page_parameter_type' => NULL,
        'page_size_parameter' => NULL,
        'page_size_parameter_type' => NULL,
      ],
      'api_key' => [
        'header_name' => NULL,
        'key' => NULL,
      ],
      'parameters' => [
        'list' => NULL,
        'single' => NULL,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['endpoint'],
    ];

    $formats = $this->responseDecoderFactory->supportedFormats();
    $form['response_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Response format'),
      '#options' => array_combine($formats, $formats),
      '#required' => TRUE,
      '#default_value' => $this->configuration['response_format'],
    ];

    $form['pager'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pager settings'),
    ];

    $form['pager']['default_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Default number of items per page'),
      '#default_value' => $this->configuration['pager']['default_limit'],
    ];

    $form['pager']['page_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page parameter'),
      '#default_value' => $this->configuration['pager']['page_parameter'],
    ];

    $form['pager']['page_parameter_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Page parameter type'),
      '#options' => [
        'pagenum' => $this->t('Page number'),
        'startitem' => $this->t('Starting item'),
      ],
      '#description' => $this->t('Use "Page number" when the pager uses page numbers to determine the item to start at, use "Starting item" when the pager uses the item number to start at.'),
      '#default_value' => $this->configuration['pager']['page_parameter_type'],
    ];

    $form['pager']['page_size_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page size parameter'),
      '#default_value' => $this->configuration['pager']['page_size_parameter'],
    ];

    $form['pager']['page_size_parameter_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Page size parameter type'),
      '#options' => [
        'pagesize' => $this->t('Number of items per page'),
        'enditem' => $this->t('Ending item'),
      ],
      '#description' => $this->t('Use "Number of items per pager" when the pager uses this parameter to determine the amount of items on each page, use "Ending item when the pager uses this parameter to determine the number of the last item on the page.'),
      '#default_value' => $this->configuration['pager']['page_size_parameter_type'],
    ];

    $form['api_key']['header_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header name'),
      '#description' => $this->t('The HTTP header name for the API key. Leave blank if no API key is required.'),
      '#default_value' => $this->configuration['api_key']['header_name'],
    ];

    $form['api_key']['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('The API key needed to communicate with the entered endpoint. Leave blank if no API key is required.'),
      '#default_value' => $this->configuration['api_key']['key'],
    ];

    $form['parameters'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Parameters'),
    ];

    $form['parameters']['list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List parameters'),
      '#description' => $this->t('Enter the parameters to add to the endpoint URL when loading the list of entities. One per line in the format "parameter_name|parameter_value"'),
      '#default_value' => $this->getParametersFormDefaultValue('list'),
    ];

    $form['parameters']['single'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Single parameters'),
      '#description' => $this->t('Enter the parameters to add to the endpoint URL when loading a single of entities. One per line in the format "parameter_name|parameter_value"'),
      '#default_value' => $this->getParametersFormDefaultValue('single'),
    ];

    return $form;
  }

  /**
   * Helper function to convert a parameter collection to a string.
   *
   * @param string $type
   *   The type of parameters (eg. 'list' or 'single').
   *
   * @return string|null
   *   A string to be used as default value, or NULL if no parameters.
   */
  protected function getParametersFormDefaultValue($type) {
    $default_value = NULL;

    if (!empty($this->configuration['parameters'][$type])) {
      $lines = [];
      foreach ($this->configuration['parameters'][$type] as $key => $value) {
        $array = array_unique([$key, $value]);
        $lines[] = implode('|', $array);
      }
      $default_value = implode("\n", $lines);
    }

    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('endpoint', rtrim($form_state->getValue('endpoint'), '/'));

    $parameters = $form_state->getValue('parameters');
    foreach ($parameters as $type => $value) {
      $lines = explode("\n", $value);
      $lines = array_map('trim', $lines);
      $lines = array_filter($lines, 'strlen');
      $parameters[$type] = [];
      foreach ($lines as $line) {
        $exploded = explode('|', $line);
        $value = !empty($exploded[1]) ? $exploded[1] : $exploded[0];
        $parameters[$type][$exploded[0]] = $value;
      }
    }
    $form_state->setValue('parameters', $parameters);

    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function delete(ExternalEntityInterface $entity) {
    $this->httpClient->request(
      'DELETE',
      $this->configuration['endpoint'] . '/' . $entity->id(),
      [
        'headers' => $this->getHttpHeaders(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    $data = [];

    if (!empty($ids) && is_array($ids)) {
      foreach ($ids as $id) {
        $data[$id] = $this->load($id);
      }
    }

    return $data;
  }

  /**
   * Loads one entity.
   *
   * @param mixed $id
   *   The ID of the entity to load.
   *
   * @return array|null
   *   A raw data array, NULL if no data returned.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function load($id) {
    $response = $this->httpClient->request(
      'GET',
      $this->configuration['endpoint'] . '/' . $id,
      [
        'headers' => $this->getHttpHeaders(),
        'query' => $this->getSingleQueryParameters($id),
      ]
    );

    $body = $response->getBody();

    return $this
      ->getResponseDecoderFactory()
      ->getDecoder($this->configuration['response_format'])
      ->decode($body);
  }

  /**
   * {@inheritdoc}
   */
  public function save(ExternalEntityInterface $entity) {
    if ($entity->id()) {
      $this->httpClient->request(
        'PUT',
        $this->configuration['endpoint'] . '/' . $entity->id(),
        [
          'form_params' => $entity->extractRawData(),
          'headers' => $this->getHttpHeaders(),
        ]
      );
      $result = SAVED_UPDATED;
    }
    else {
      $this->httpClient->request(
        'POST',
        $this->configuration['endpoint'],
        [
          'form_params' => $entity->extractRawData(),
          'headers' => $this->getHttpHeaders(),
        ]
      );
      $result = SAVED_NEW;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters = [], array $sorts = [], $start = NULL, $length = NULL) {
    $response = $this->httpClient->request(
      'GET',
      $this->configuration['endpoint'],
      [
        'headers' => $this->getHttpHeaders(),
        'query' => $this->getListQueryParameters($parameters, $start, $length),
      ]
    );

    $body = $response->getBody() . '';

    $results = $this
      ->getResponseDecoderFactory()
      ->getDecoder($this->configuration['response_format'])
      ->decode($body);

    return $results;
  }

  /**
   * Prepares and returns parameters used for list queries.
   *
   * @param array $parameters
   *   (optional) Raw parameter values.
   * @param int|null $start
   *   (optional) The first item to return.
   * @param int|null $length
   *   (optional) The number of items to return.
   *
   * @return array
   *   An associative array of parameters.
   */
  public function getListQueryParameters(array $parameters = [], $start = NULL, $length = NULL) {
    $query_parameters = [];

    // Currently always providing a limit.
    $query_parameters += $this->getPagingQueryParameters($start, $length);

    foreach ($parameters as $parameter) {
      // TODO: Apply parameter operator.
      $query_parameters[$parameter['field']] = is_array($parameter['value'])
        ? implode(',', $parameter['value'])
        : $parameter['value'];
    }

    if (!empty($this->configuration['parameters']['list'])) {
      $query_parameters += $this->configuration['parameters']['list'];
    }

    return $query_parameters;
  }

  /**
   * Gets the paging query parameters based on the configuration.
   *
   * @param int $start
   *   (optional) Item index to start with.
   * @param int $length
   *   (optional) Amount of items to return.
   *
   * @return array
   *   An associative array of paging parameters.
   */
  public function getPagingQueryParameters($start = NULL, $length = NULL) {
    $paging_parameters = [];

    if ($this->configuration['pager']['page_parameter'] && $this->configuration['pager']['page_size_parameter']) {
      $start = $start ?: 0;
      $end = $length ?: $this->configuration['pager']['default_limit'];

      if ($this->configuration['pager']['page_parameter_type'] === 'pagenum') {
        $start = $start / $end;
      }

      if ($this->configuration['pager']['page_size_parameter_type'] === 'enditem') {
        $end = $start + $end;
      }

      $paging_parameters[$this->configuration['pager']['page_parameter']] = $start;
      $paging_parameters[$this->configuration['pager']['page_size_parameter']] = $end;
    }

    return $paging_parameters;
  }

  /**
   * Prepares and returns parameters used for single item queries.
   *
   * @param int|string $id
   *   The item id being fetched.
   * @param array $parameters
   *   (optional) Raw parameter values.
   *
   * @return array
   *   An associative array of parameters.
   */
  public function getSingleQueryParameters($id, array $parameters = []) {
    $query_parameters = [];

    foreach ($parameters as $parameter) {
      // TODO: Apply parameter operator.
      $query_parameters[$parameter['field']] = is_array($parameter['value'])
        ? implode(',', $parameter['value'])
        : $parameter['value'];
    }

    if (!empty($this->configuration['parameters']['single'])) {
      $query_parameters += $this->configuration['parameters']['single'];
    }

    return $query_parameters;
  }

  /**
   * Gets the HTTP headers to add to a request.
   *
   * @return array
   *   Associative array of headers to add to the request.
   */
  public function getHttpHeaders() {
    $headers = [];

    if ($this->configuration['api_key']['header_name'] && $this->configuration['api_key']['key']) {
      $headers[$this->configuration['api_key']['header_name']] = $this->configuration['api_key']['key'];
    }

    return $headers;
  }

}
