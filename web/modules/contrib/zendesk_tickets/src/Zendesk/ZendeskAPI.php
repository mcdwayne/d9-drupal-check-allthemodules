<?php

namespace Drupal\zendesk_tickets\Zendesk;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zendesk\API\Utilities\Auth as ZendeskAuth;
use Zendesk\API\Exceptions\ApiResponseException as ZendeskApiResponseException;
use Drupal\Component\Serialization\Json;
use \Exception;

/**
 * Provides a Zendesk API handler.
 */
class ZendeskAPI implements ContainerInjectionInterface, EntityHandlerInterface {

  const AUTH_STRATEGY_DEFAULT = ZendeskAuth::BASIC;

  /**
   * The config object for the Zendesk tickets module.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * TRUE if Zendesk access is enabled.
   *
   * @var bool
   */
  protected $enabled;

  /**
   * The Zendesk subdomain.
   *
   * @var string
   */
  protected $subdomain;

  /**
   * The Zendesk authentication strategy.
   *
   * @var string
   */
  protected $authStrategy;

  /**
   * The Zendesk authentication username.
   *
   * @var string
   */
  protected $authUsername;

  /**
   * The Zendesk authentication access token.
   *
   * @var string
   */
  protected $authAccessToken;

  /**
   * The HTTP client for the Zendesk API.
   *
   * @var \Drupal\zendesk_tickets\Zendesk\HttpClient
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A test handler for the Zendesk API.
   *
   * @var \Drupal\zendesk_tickets\Zendesk\ZendeskAPITest
   */
  protected $tester;

  /**
   * Constructs a Zendesk Form Controller.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory object being edited.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger factory object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->config = $config_factory->get('zendesk_tickets.settings');
    $this->logger = $logger_factory->get('zendesk_tickets');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * Returns a list of supported Zendesk Authorization strategies.
   *
   * @return array
   *   An array of strategies with keys of ZendeskAuth constants and values of
   *   human readable labels.
   */
  public static function supportedAuthStrategies() {
    return [
      ZendeskAuth::BASIC => 'Basic authentication with API Token',
      ZendeskAuth::OAUTH => 'OAuth Token',
    ];
  }

  /**
   * Determine if API access is enabled.
   *
   * @return bool
   *   TRUE if access is enabled.
   */
  public function isEnabled() {
    if (!isset($this->enabled) && $this->config) {
      $this->enabled = $this->config->get('enabled');
    }
    return $this->enabled;
  }

  /**
   * Determine if access to Zendesk is possible.
   *
   * @return bool
   *   TRUE if access is possible.
   */
  public function isCapable() {
    return $this->isEnabled() &&
      $this->getSubdomain() &&
      $this->getAuthUsername() &&
      $this->getAuthAccessToken();
  }

  /**
   * Get the subdomain.
   *
   * @return string
   *   The authentication strategy.
   */
  public function getSubdomain() {
    if (!isset($this->subdomain) && $this->config) {
      $this->subdomain = $this->config->get('subdomain', '');
    }

    return $this->subdomain;
  }

  /**
   * Determines the active authentication strategy.
   *
   * @return string
   *   The authentication strategy.
   */
  public function getAuthStrategy() {
    if (!isset($this->authStrategy) && $this->config) {
      $strategy = $this->config->get('auth_strategy', static::AUTH_STRATEGY_DEFAULT);
      $supported_auths = static::supportedAuthStrategies();
      if (isset($supported_auths[$strategy])) {
        $this->authStrategy = $strategy;
      }
    }

    return $this->authStrategy;
  }

  /**
   * Get the human readable label of the active authentication strategy.
   *
   * @return string
   *   The authentication strategy label.
   */
  public function getAuthStrategyLabel() {
    $strategy = $this->getAuthStrategy();
    if ($strategy) {
      $supported_auths = static::supportedAuthStrategies();
      return isset($supported_auths[$strategy]) ? $supported_auths[$strategy] : NULL;
    }
  }

  /**
   * Get the username.
   *
   * @return string
   *   The authentication username.
   */
  public function getAuthUsername() {
    if (!isset($this->authUsername) && $this->config) {
      $this->authUsername = $this->config->get('username', '');
    }

    return $this->authUsername;
  }

  /**
   * Get the access token.
   *
   * @return string
   *   The authentication access token.
   */
  public function getAuthAccessToken() {
    if (!isset($this->authAccessToken) && $this->config) {
      $this->authAccessToken = $this->config->get('access_token', '');
    }

    return $this->authAccessToken;
  }

  /**
   * Create the HTTP client.
   *
   * @return HttpClient|null
   *   The HTTP client object.
   */
  protected function createHttpClient() {
    $this->httpClient = NULL;
    if ($this->isCapable()) {
      $this->httpClient = new HttpClient($this->getSubdomain());
      $this->httpClient->setAuth($this->getAuthStrategy(), [
        'username' => $this->getAuthUsername(),
        'token' => $this->getAuthAccessToken(),
      ]);
    }
    return $this->httpClient;
  }

  /**
   * Get the API HTTP Client.
   *
   * @return HttpClient|null
   *   The HTTP client object.
   */
  public function httpClient() {
    return isset($this->httpClient) ? $this->httpClient : $this->createHttpClient();
  }

  /**
   * Get the ticket forms resource.
   *
   * @return \Zendesk\API\Resources\Core\TicketForms|null
   *   The TicketForms resource object.
   */
  public function ticketFormsResource() {
    $client = $this->httpClient();
    if ($client) {
      return $this->ticketsResource()->forms();
    }
  }

  /**
   * Get the ticket fields resource.
   *
   * @return \Zendesk\API\Resources\Core\TicketFields|null
   *   The TicketForms resource object.
   */
  public function ticketFieldsResource() {
    $client = $this->httpClient();
    if ($client) {
      return $client->ticketFields();
    }
  }

  /**
   * Get the tickets resource.
   *
   * @return \Zendesk\API\Resources\Core\Tickets|null
   *   The Tickets resource object.
   */
  public function ticketsResource() {
    $client = $this->httpClient();
    if ($client) {
      return $client->tickets();
    }
  }

  /**
   * Get all ticket forms that are active and visible.
   *
   * Endpoint: GET /api/v2/ticket_forms.json.
   * https://developer.zendesk.com/rest_api/docs/core/ticket_forms.
   *
   * @param array $form_ids
   *   An array of ticket form ids.
   * @param array $filter_params
   *   An array of parameters to filter the request. See the doc url above for
   *   all parameters. Initially this function returned only forms that were
   *   ['active' => TRUE, 'end_user_visible' => TRUE], however this whitelist
   *   approach would not retrieve a form that was disabled on Zendesk. If that
   *   formw was previously imported then it needs to be disabled in Drupal.
   *
   * @return array
   *   An array of ticket form objects.
   */
  public function fetchTicketForms(array $form_ids = [], array $filter_params = []) {
    $forms = [];
    $forms_resource = $this->ticketFormsResource();
    if ($forms_resource) {
      $response = NULL;
      try {
        $filter_params = !empty($filter_params) ? $filter_params : [];
        // TODO: Remove restriction?
        // Group into a select list per Zendesk organization and brand?
        $filter_params = $filter_params + [
          'in_all_organizations' => TRUE,
          'in_all_brands' => TRUE,
        ];
        $response = $forms_resource->findAll($filter_params);

        if ($response && isset($response->ticket_forms)) {
          // Id the forms array and find all the fields.
          foreach ($response->ticket_forms as $form) {
            if (isset($form->id)) {
              $forms[$form->id] = $form;
            }
          }

          if ($form_ids) {
            $forms = array_intersect_key($forms, array_combine($form_ids, $form_ids));
          }

          // Resolve the form objects.
          $this->resolveTicketForms($forms);
        }
      }
      catch (ZendeskApiResponseException $e) {
        // Log API errors.
        $error_message = @$this->getErrorMessage($e);
        $error_message = $error_message ?: 'Unknown error';
        $this->logger->warning('An error occurred while fetching Zendesk ticket forms: @error', [
          '@error' => $error_message,
        ]);
      }
    }

    return $forms;
  }

  /**
   * Get ticket forms that are active and visible.
   *
   * Endpoint: GET /api/v2/ticket_forms.json.
   * https://developer.zendesk.com/rest_api/docs/core/ticket_forms.
   *
   * @param array $fields_ids
   *   An array of field ids.
   * @param array $filter_params
   *   An array of parameters to filter the request.
   *
   * @return array
   *   An array of ticket form objects.
   */
  protected function fetchTicketFields(array $fields_ids = [], array $filter_params = []) {
    $return = [];
    $fields_resource = $this->ticketFieldsResource();
    if ($fields_resource) {
      $response = NULL;
      try {
        $filter_params = !empty($filter_params) ? $filter_params : [];
        $filter_params = $filter_params + [
          'active' => TRUE,
          'visible_in_portal' => TRUE,
          'editable_in_portal' => TRUE,
        ];
        $response = $fields_resource->findAll($filter_params);

        if ($response && !empty($response->ticket_fields)) {
          $fields = [];
          foreach ($response->ticket_fields as $field) {
            // Skip if missing field id.
            if (!isset($field->id)) {
              continue;
            }

            // Apply filter, findAll() did not filter when passed to it.
            $field_is_valid = TRUE;
            foreach ($filter_params as $filter_key => $filter_value) {
              if (isset($field->{$filter_key}) && $field->{$filter_key} != $filter_value) {
                $field_is_valid = FALSE;
                break;
              }
            }

            if (!$field_is_valid) {
              continue;
            }

            // Id the field.
            $fields[$field->id] = $field;
          }

          // Build return.
          if ($fields) {
            if ($fields_ids) {
              // Return fields for the provided field ids in the same order.
              foreach ($fields_ids as $fields_id) {
                if (isset($fields[$fields_id])) {
                  $return[$fields_id] = $fields[$fields_id];
                }
              }
            }
            else {
              // Return all.
              $return = $fields;
            }
          }
        }
      }
      catch (ZendeskApiResponseException $e) {
        // Log API errors.
        $error_message = @$this->getErrorMessage($e);
        $error_message = $error_message ?: 'Unknown error';
        $this->logger->warning('An error occurred while fetching Zendesk ticket fields: @error', [
          '@error' => $error_message,
        ]);
      }
    }

    return $return;
  }

  /**
   * Resolves all references within the ticket forms.
   *
   * The following are added to each form object:
   *   - ticket_fields: The full field objects referenced in ticket_field_ids.
   *
   * @param array $forms
   *   An array of ticket form objects.
   */
  public function resolveTicketForms($forms) {
    $field_ids = [];
    foreach ($forms as $form) {
      if (isset($form->id)) {
        $forms[$form->id] = $form;
      }

      // Initialize fields.
      $form->ticket_fields = [];

      // Build all field ids.
      if (isset($form->ticket_field_ids)) {
        $field_ids = array_merge($field_ids, $form->ticket_field_ids);
      }
    }

    // Fetch unique fields.
    $field_ids = array_unique($field_ids);
    if ($field_ids) {
      $fields = $this->fetchTicketFields($field_ids);
      if ($fields) {
        foreach ($forms as $form) {
          if (isset($form->ticket_field_ids)) {
            foreach ($form->ticket_field_ids as $field_id) {
              if (isset($fields[$field_id])) {
                $form->ticket_fields[$field_id] = $fields[$field_id];
              }
            }
          }
        }
      }
    }
  }

  /**
   * Create a ticket with the given values.
   *
   * Endpoint: GET /api/v2/tickets.json.
   * https://developer.zendesk.com/rest_api/docs/core/tickets#create-ticket.
   *
   * @param array $params
   *   An array of parameters per the Zendesk API tickets.
   * @param bool $purge_file_uploads
   *   Remove local files after they have been sent to Zendesk.
   *   Defaults to TRUE.
   *
   * @return object|null
   *   The ticket creation response object.
   */
  public function createTicket(array $params, $purge_file_uploads = TRUE) {
    $return = NULL;
    $resource = $this->ticketsResource();
    if ($resource) {
      try {
        if (!empty($params['file_uploads'])) {
          foreach ($params['file_uploads'] as $upload) {
            if (!empty($upload['file'])) {
              $file = $upload['file'];

              // Send file to zendesk.
              $resource->attach([
                'file' => drupal_realpath($file->getFileUri()),
                'type' => $file->getMimeType(),
                'name' => !empty($upload['name']) ?: $file->getFilename(),
              ]);

              // Purge file.
              if ($purge_file_uploads) {
                $file->delete();
              }
            }
          }
          unset($params['file_uploads']);
        }

        $return = $resource->create($params);
      }
      catch (ZendeskApiResponseException $e) {
        // Log API errors.
        $error_message = @$this->getErrorMessage($e);
        $error_message = $error_message ?: 'Unknown error';
        $this->logger->warning('An error occurred while creating a Zendesk ticket for @ticket_form_id: @error', [
          '@error' => $error_message,
          '@ticket_form_id' => isset($params['ticket_form_id']) ? $params['ticket_form_id'] : 'unknown ticket form',
        ]);
      }
    }

    return $return;
  }

  /**
   * Spawn tester.
   *
   * @return ZendeskAPITest
   *   A ZendeskAPITest object.
   */
  public function getTester() {
    if (!isset($this->tester)) {
      $this->tester = new ZendeskAPITest($this);
    }

    return $this->tester;
  }

  /**
   * Build an error message for the given api error.
   *
   * @param Exception $error
   *   The error object.
   *
   * @return string
   *   An error message.
   */
  public function getErrorMessage(Exception $error) {
    $message = '';

    // Zendesk API errors.
    if (($error instanceof ZendeskApiResponseException) && $error->getErrorDetails()) {
      $details = @Json::decode($error->getErrorDetails());
      if (!empty($details['error'])) {
        $parts = [];
        if (is_array($details['error'])) {
          if (!empty($details['error']['title'])) {
            $parts[] = $details['error']['title'];
          }
          if (!empty($details['error']['message'])) {
            $parts[] = $details['error']['message'];
          }
        }

        if (!empty($details['description']) && is_scalar($details['description'])) {
          $parts[] = $details['description'];
        }

        if (!empty($details['details']) && is_array($details['details'])) {
          $details_messages = [];
          foreach ($details['details'] as $details_key => $details_info) {
            if (is_array($details_info)) {
              foreach ($details_info as $details_info_values) {
                if (isset($details_info_values['description'])) {
                  $details_messages[] = $details_key . ': ' . $details_info_values['description'];
                }
              }
            }
          }

          if ($details_messages) {
            $parts[] = implode('; ', $details_messages);
          }
        }

        if ($parts) {
          $message = implode(' - ', $parts);
        }
      }
    }

    // Fallback to default message.
    if (empty($message)) {
      $message = $error->getMessage();
    }

    return $message;
  }

}
