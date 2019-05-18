<?php

namespace Drupal\redmine_connector;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ConnectService.
 */
class ConnectService {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Drupal Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  public $logger;

  /**
   * Drupal\Core\Cache\CacheBackendInterface definition.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Current user.
   *
   * @var \Drupal\user\Plugin\views\argument_default\CurrentUser
   */
  protected $currentUser;

  /**
   * The user instance.
   *
   * @var \Drupal\user\Plugin\views\argument_default\CurrentUser
   */
  protected $user;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $current_user, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config, Client $http_client, LoggerChannelFactory $logger) {
    $this->httpClient = $http_client;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger->get('redmine_connector');
    $this->request = $request_stack->getCurrentRequest();
    $this->config = $config->get('redmine_connector.redmine_connector_config');
  }

  /**
   * Method to load data from the Redmine.
   *
   * @param string $type
   *   Resource of the request. See Redmine REST API to clarify
   *   http://www.redmine.org/projects/redmine/wiki/Rest_api.
   * @param array $options
   *   Array with date and user id.
   *
   * @return bool|array
   *   Array of selected data or FALSE.
   */
  public function getData($type, array $options = []) {
    // If cache is empty run query.
    $parameters = [
      'limit' => 20000,
    ];

    // Add user id if present.
    if (!empty($options['user_id'])) {
      $parameters['user_id'] = $options['user_id'];
    }
    switch ($type) {
      case 'time_entries':
        if (!empty($options['date'])) {
          $parameters['spent_on'] = '><' . $options['date'];
        }
        if (!empty($options['project_id'])) {
          $parameters['project_id'] = $options['project_id'];
        }
        $url = $this->config->get('redmine_url') . '/' . $type . '.json?' . http_build_query($parameters);
        break;

      case 'issues':
        // Add user id if present.
        if (!empty($options['assigned_to_id'])) {
          $parameters['assigned_to_id'] = $options['assigned_to_id'];
        }
        $url = $this->config->get('redmine_url') . '/' . $type . '.json?' . http_build_query($parameters);
        break;

      case 'users':
        $parameters['status_id'] = '*';
        // Add user id if present.
        if (!empty($options['user_id'])) {
          $parameters['assigned_to_id'] = $options['user_id'];
        }
        $url = $this->config->get('redmine_url') . '/' . $type . '.json?' . http_build_query($parameters);
        break;

      case 'groups/116':
      case 'groups/117':
      case 'groups/118':
      case 'groups/119':
      case 'groups/121':
      case 'groups':
        $parameters['include'] = 'users';
        $url = $this->config->get('redmine_url') . '/' . $type . '.json?' . http_build_query($parameters);
        break;

      case 'projects':
        $parameters['limit'] = '250';
        $url = $this->config->get('redmine_url') . '/' . $type . '.json?' . http_build_query($parameters);
        break;

      case 'memberships':
        if (!empty($options['project_id'])) {
          $url = $this->config->get('redmine_url') . '/projects/' . $options['project_id'] . '/memberships.json';
        }
    }

    $login = $this->config->get('redmine_login');
    $pass = $this->config->get('redmine_pass');
    // Try to make a request.
    try {
      $headers = [
        // ToDo. Move to token authentication.
        'Authorization' => 'Basic ' . base64_encode($login . ':' . $pass),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
      ];
      $request = new Request('GET', $url, $headers);
      $result = $this->httpClient->send($request);
    }
    // Process request error.
    catch (RequestException $e) {
      $this->logger->notice($e->getCode() . ' - ' . $e->getMessage());
    }

    // Set cache and return result.
    if (!empty($result)) {
      $data = Json::decode($result->getBody()->getContents());
      return $data;
    }
    else {
      return FALSE;
    }
  }
}
