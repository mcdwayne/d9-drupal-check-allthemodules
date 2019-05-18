<?php

namespace Drupal\semantic_connector\Entity;
use Drupal\semantic_connector\Api\SemanticConnectorPPTApi;
use Drupal\semantic_connector\Api\SemanticConnectorPPXApi;
use Drupal\semantic_connector\Api\SemanticConnectorSonrApi;
use Drupal\semantic_connector\SemanticConnector;

/**
 * @ConfigEntityType(
 *   id ="pp_server_connection",
 *   label = @Translation("PoolParty Server connection"),
 *   handlers = {
 *     "list_builder" = "Drupal\semantic_connector\ConnectionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionForm",
 *       "add" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionForm",
 *       "edit" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionForm",
 *       "delete" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionDeleteForm"
 *     }
 *   },
 *   config_prefix = "pp_server_connection",
 *   admin_permission = "administer semantic connector",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/semantic-drupal/semantic-connector/connections/pp-server/{pp_server_connection}/delete",
 *     "edit-form" = "/admin/config/semantic-drupal/semantic-connector/connections/pp-server/{pp_server_connection}",
 *     "collection" = "/admin/config/semantic-drupal/semantic-connector/",
 *   },
 *   config_export = {
 *     "title",
 *     "id",
 *     "type",
 *     "url",
 *     "credentials",
 *     "config",
 *   }
 * )
 */
class SemanticConnectorPPServerConnection extends SemanticConnectorConnection {
  /**
   * The constructor of the SemanticConnectorPPServerConnection class.
   *
   * {@inheritdoc|}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->type = 'pp_server';
  }

  /**
   * {@inheritdoc|}
   */
  public function available() {
    $availability = $this->getApi('PPX')->available();
    return $availability['success'];
  }

  /**
   * Adds PoolParty projects and SPARQL endpoints before saving it.
   */
  public function save() {
    // Update the PoolParty version.
    $ppt_api = $this->getApi('PPT');
    $this->config['version'] = $ppt_api->getVersion();

    // Add the projects to the config before saving the PoolParty server.
    $projects = $ppt_api->getProjects();

    // Get the Sparql-Endpoints for each project.
    $sparql_endpoints = array();
    foreach ($projects as $project) {
      if (isset($project['sparql_endpoint_url'])) {
        $sparql_endpoints[] = $project['sparql_endpoint_url'];
      }
    }

    // Get all not existing Sparql-Endpoints for removing.
    $sparql_endpoints_to_remove = array();
    if (isset($this->config['projects'])) {
      foreach ($this->config['projects'] as $project) {
        if (isset($project['sparql_endpoint_url']) && !in_array($project['sparql_endpoint_url'], $sparql_endpoints)) {
          $sparql_endpoints_to_remove[] = $project['sparql_endpoint_url'];
        }
      }
    }

    // Add the projects to the configuration.
    $this->config['projects'] = $projects;

    // Update the PoolParty GraphSearch configuration.
    $graphsearch_config = array();
    $graphsearch_api = $this->getApi('sonr');
    // Get the version of the sOnr web service.
    $graphsearch_version = $graphsearch_api->getVersion();

    // Get the appropriate API for the correct version.
    $this->config['graphsearch_configuration'] = array(
      'version' => $graphsearch_version,
    );
    $graphsearch_api = $this->getApi('sonr');

    // If a PoolParty GraphSearch server exists, create a config.
    if (!empty($graphsearch_version)) {
      // Get the server-side configuration and save it also to the database.
      $graphsearch_config = $graphsearch_api->getConfig();
      $graphsearch_config['version'] = $graphsearch_version;
    }
    $this->config['graphsearch_configuration'] = $graphsearch_config;

    parent::save();

    // Add a SPARQL-endpoint connection for every project.
    foreach ($this->config['projects'] as $project) {
      if (isset($project['sparql_endpoint_url'])) {
        SemanticConnector::createConnection('sparql_endpoint', $project['sparql_endpoint_url'], $project['title'], $this->credentials, array());
      }
    }

    // Remove SPARQL-endpoints, that do not exist anymore.
    if (!empty($sparql_endpoints_to_remove)) {
      $connections_query = \Drupal::entityQuery('sparql_endpoint');
      $delete_connection_ids = $connections_query->condition('url', $sparql_endpoints_to_remove, 'IN')->execute();

      SemanticConnector::deleteConnections('sparql_endpoint', $delete_connection_ids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete the Sparql-Endpoint for each project.
    if (isset($this->config['projects'])) {
      $sparql_endpoints_to_remove = array();
      foreach ($this->config['projects'] as $project) {
        if (isset($project['sparql_endpoint_url'])) {
          $sparql_endpoints_to_remove[] = $project['sparql_endpoint_url'];
        }
      }

      if (!empty($sparql_endpoints_to_remove)) {
        $connections_query = \Drupal::entityQuery('sparql_endpoint_connection');
        $delete_connection_ids = $connections_query->condition('url', $sparql_endpoints_to_remove, 'IN')->execute();

        SemanticConnector::deleteConnections('sparql_endpoint', $delete_connection_ids);
      }
    }

    parent::delete();
  }

  /**
   * Returns the API to a specific type.
   *
   * @param string $api_type
   *   The desired API type. Possible values are:
   *   - "PPX": The PoolParty Extraction service API
   *   - "PPT": The PoolParty Thesaurus API
   *   - "sonr": The sOnr webMining server API
   *
   * @return SemanticConnectorSonrApi|SemanticConnectorPPTApi|SemanticConnectorPPXApi
   *   The specific API.
   */
  public function getApi($api_type = 'PPX') {
    if (in_array($api_type, array('PPX', 'PPT', 'sonr'))) {
      $api_version_info = $this->getVersionInfo($api_type);
      $credentials = !empty($this->credentials['username']) ? $this->credentials['username'] . ':' . $this->credentials['password'] : '';

      // PPX or PPT API.
      if ($api_type != 'sonr') {
        return new $api_version_info['api_class_name']($this->url, $credentials);
      }
      // sOnr API.
      else {
        // Use an overridden GraphSearch path if available.
        $connection_overrides = \Drupal::config('semantic_connector.settings')->get('override_connections');
        $custom_graphsearch_path = '';
        if (isset($connection_overrides[$this->id()]) && isset($connection_overrides[$this->id()]['graphsearch_path'])) {
          $custom_graphsearch_path = $connection_overrides[$this->id()]['graphsearch_path'];
        }

        /** @var SemanticConnectorSonrApi $sonr_api */
        $sonr_api = new $api_version_info['api_class_name']($this->url, $credentials, $custom_graphsearch_path);
        $sonr_api->setId($this->id);
        return $sonr_api;
      }
    }
    else {
      return NULL;
    }
  }

  /**
   * Get all information about the version of a API available on the PP server.
   *
   * @param string $api_type
   *   The desired API type. Possible values are:
   *   - "PPX": The PoolParty Extraction service API
   *   - "PPT": The PoolParty Thesaurus API
   *   - "sonr": The sOnr webMining server API
   *
   * @return array
   *   An associative array containing following keys:
   *   - "installed_version": The current version of the API service
   *   - "latest_version": The latest API implementation for the service
   *   - "api_class_name": The class name of the appropriate API class to use
   */
  public function getVersionInfo($api_type) {
    // List of finished API version implementations. Only add versions to this
    // list when they are fully functional. The order of the versions is not
    // important.
    $available_api_versions = array(
      'pp_server' => array('4.6', '5.3', '5.6', '6.0', '6.2', '7.0'),
      'sonr' => array('4.6', '5.3', '5.6', '5.7', '6.0', '6.1', '7.0'),
    );

    $version_infos = array(
      'installed_version' => '',
      'latest_version' => '',
      'api_class_name' => '',
    );

    // PPX or PPT API.
    if ($api_type != 'sonr') {
      $api_versions = $available_api_versions['pp_server'];
      $class_prefix = '\Drupal\semantic_connector\Api\SemanticConnector' . $api_type . 'Api_';
      usort($api_versions, 'version_compare');
      if (!isset($this->config['version']) || empty($this->config['version'])) {
        $this->config['version'] = $api_versions[0];
        // Check with the lowest API version, which supports getVersion for all
        // API versions.
        $version_check_class_name = '\Drupal\semantic_connector\Api\SemanticConnectorPPTApi_' . str_replace('.', '_', $api_versions[0]);
        $credentials = !empty($this->credentials['username']) ? $this->credentials['username'] . ':' . $this->credentials['password'] : '';

        /** @var SemanticConnectorPPTApi $ppt_api */
        $ppt_api = new $version_check_class_name($this->url, $credentials);
        $this->config['version'] = $ppt_api->getVersion();
      }
      $version_infos['installed_version'] = $this->config['version'];
    }
    // sOnr API.
    else {
      $api_versions = $available_api_versions['sonr'];
      usort($api_versions, 'version_compare');
      $class_prefix = '\Drupal\semantic_connector\Api\SemanticConnectorSonrApi_';
      if (!isset($this->config['graphsearch_configuration']) || !isset($this->config['graphsearch_configuration']['version']) || empty($this->config['graphsearch_configuration']['version'])) {
        // Check with the lowest API version, which supports getVersion for all
        // API versions.
        $version_check_class_name = $class_prefix . str_replace('.', '_', $api_versions[0]);
        $credentials = !empty($this->credentials['username']) ? $this->credentials['username'] . ':' . $this->credentials['password'] : '';

        $custom_graphsearch_path = $this->getGraphSearchPath();
        /** @var SemanticConnectorSonrApi $sonr_api */
        $sonr_api = new $version_check_class_name($this->url, $credentials, ($custom_graphsearch_path != 'sonr-backend' ? $custom_graphsearch_path : ''));

        $this->config['graphsearch_configuration']['version'] = $sonr_api->getVersion();
      }
      $version_infos['installed_version'] = $this->config['graphsearch_configuration']['version'];
    }

    // To get the newest compatible API version, we have to reverse the array
    // and check every single version.
    $api_versions = array_reverse($api_versions);
    $version_infos['latest_version'] = $api_versions[0];
    foreach ($api_versions as $current_api_version) {
      if (version_compare($version_infos['installed_version'], $current_api_version, '>=')) {
        $class_version = $current_api_version;
        break;
      }
    }
    if (!isset($class_version)) {
      $class_version = $api_versions[count($api_versions) - 1];
    }
    $version_infos['api_class_name'] = $class_prefix . str_replace('.', '_', $class_version);

    return $version_infos;
  }

  /**
   * Get the path to the GraphSearch connected to the PP server.
   *
   * @return string
   *   The path to the GraphSearch instance
   */
  public function getGraphSearchPath() {
    $graphsearch_path = '';
    if (isset($this->config['graphsearch_configuration']) && isset($this->config['graphsearch_configuration']['version']) && !empty($this->config['graphsearch_configuration']['version'])) {
      $graphsearch_path = 'sonr-backend';
      if (version_compare($this->config['graphsearch_configuration']['version'], '5.6.0', '>=')) {
        $graphsearch_path = 'GraphSearch';
        // Use an overridden GraphSearch path if available.
        $connection_overrides = \Drupal::config('semantic_connector.settings')->get('override_connections');
        if (isset($connection_overrides[$this->id()]) && isset($connection_overrides[$this->id()]['graphsearch_path'])) {
          $graphsearch_path = $connection_overrides[$this->id()]['graphsearch_path'];
        }
      }
    }

    return $graphsearch_path;
  }

  /**
   * Get the SPARQL endpoints for this PP Server connection.
   *
   * @param string $project_id
   *   Optional; If given only SPARQL endpoints for that project will be returned.
   *
   * @return array
   *   An array of connection IDs of SPARQL endpoint connections.
   */
  public function getSparqlEndpoints($project_id = '') {
    $sparql_endpoints = [];

    $server_config = $this->getConfig();
    $sparql_endpoint_urls = [];
    if (isset($server_config['projects']) && !empty($server_config['projects'])) {
      foreach ($server_config['projects'] as $project) {
        if ((empty($project_id) || $project['id'] === $project_id) && isset($project['sparql_endpoint_url'])) {
          $sparql_endpoint_urls[] = $project['sparql_endpoint_url'];
        }
      }
    }

    if (!empty($sparql_endpoint_urls)) {
      $sparql_endpoints = SemanticConnector::searchConnections('sparql_endpoint', [
        'url' => $sparql_endpoint_urls
      ]);
    }

    return $sparql_endpoints;
  }

  /**
   * {@inheritdoc|}
   */
  public function getDefaultConfig() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function exist($id) {
    $entity_count = \Drupal::entityQuery('pp_server_connection')
      ->condition('id', $id)
      ->count()
      ->execute();
    return (bool) $entity_count;
  }
}