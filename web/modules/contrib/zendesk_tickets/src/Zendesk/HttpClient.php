<?php

namespace Drupal\zendesk_tickets\Zendesk;

use Zendesk\API\HttpClient as ZendeskApiHttpClient;
use Drupal\zendesk_tickets\Zendesk\Resources\Core\Tickets;

/**
 * Customized Zendesk HttpClient.
 */
class HttpClient extends ZendeskApiHttpClient {

  protected $apiBasePath = 'api/v2/';

  /**
   * Allowed sub resources.
   *
   * @return array
   *   An array of sub resource names.
   */
  public static function getAllowedSubResourceNames() {
    return [
      'oauthClients',
      'oauthTokens',
      'ticketFields',
      'tickets',
      'attachments',
    ];
  }

  /**
   * Returns all valid sub resources as defined by the base HttpClient.
   *
   * @return array
   *   An array of valid sub resources.
   */
  public static function getZendeskValidSubResources() {
    return parent::getValidSubResources();
  }

  /**
   * {@inheritdoc}
   *
   * Restrict the valid endpoints.
   */
  public static function getValidSubResources() {
    $allowed_resource_names = static::getAllowedSubResourceNames();
    $api_resources = static::getZendeskValidSubResources();
    $resources = array_intersect_key($api_resources, array_combine($allowed_resource_names, $allowed_resource_names));

    // Tickets - Set customized class with restricted methods.
    if (isset($resources['tickets'])) {
      $resources['tickets'] = Tickets::class;
    }

    return $resources;
  }

  /**
   * {@inheritdoc}
   *
   * DENY all endpoints.
   */
  public function put($endpoint, $putData = []) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * DENY all endpoints.
   */
  public function delete($endpoint) {
    return NULL;
  }

}
