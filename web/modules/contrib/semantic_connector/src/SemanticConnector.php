<?php

/**
 * @file
 * The main class of the Semantic Connector.
 */

namespace Drupal\semantic_connector;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\pp_graphsearch\Entity\PPGraphSearchConfig;
use Drupal\pp_graphsearch\PPGraphSearch;
use Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfig;
use Drupal\semantic_connector\Entity\SemanticConnectorConnection;
use Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection;
use Drupal\semantic_connector\Entity\SemanticConnectorSparqlEndpointConnection;
use Drupal\smart_glossary\Entity\SmartGlossaryConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * A collection of static functions offered by the PoolParty Semantic Connector.
 */
class SemanticConnector {
  /**
   * Get a connection of the PoolParty Semantic Connector by its ID.
   *
   * @param string $type
   *   The type of the connection to receive. Possible values: 'pp_server',
   *   and 'sparql_endpoint'.
   * @param int $connection_id
   *   The ID of the Semantic Connector Connection.
   *
   * @return SemanticConnectorConnection
   *   The connection object, depending on the provided $type.
   */
  public static function getConnection($type, $connection_id = 0) {
    switch ($type) {
      case 'pp_server':
        if (empty($connection_id)) {
          return SemanticConnectorPPServerConnection::create();
        }
        else {
          return SemanticConnectorPPServerConnection::load($connection_id);
        }

      case 'sparql_endpoint':
        if (empty($connection_id)) {
          return SemanticConnectorSparqlEndpointConnection::create();
        }
        else {
          return SemanticConnectorSparqlEndpointConnection::load($connection_id);
        }

      default:
        return NULL;
    }
  }

  /**
   * Get all connection of the PoolParty Semantic Connector by connection-type.g
   *
   * @param string $type
   *   The type of the connections to receive. Possible values: 'pp_server'
   *   and 'sparql_endpoint'.
   *
   * @return array
   *   Array of SemanticConnectorConnection-objects of the give type.
   */
  public static function getConnectionsByType($type) {
    $controller = \Drupal::entityTypeManager()->getStorage($type . '_connection');
    return $controller->loadMultiple();
  }

  /**
   * Search for connections matching a set of search filters.
   *
   * @param string $type
   *   The type of the connections to receive. Possible values: 'pp_server'
   *   and 'sparql_endpoint'.
   * @param array $search_filters
   *   An associative array of search filters, where the key is the name of the
   *   database field to search in and the value is a string, which will
   *   filtered for on "Exact match"-basis.
   *   Possible keys are: 'url', 'title', 'username', 'password' and
   *   'config' (config needs to be a serialized array)
   *
   * @return array
   *   Array of SemanticConnectorConnection-objects matching the search filters.
   */
  public static function searchConnections($type, array $search_filters) {
    $connections_query = \Drupal::entityQuery($type . '_connection');

    $allowed_filter_keys = array('url', 'title', 'username', 'password', 'config');
    foreach ($search_filters as $search_filter_key => $search_filter_value) {
      if (in_array($search_filter_key, $allowed_filter_keys) && (is_string($search_filter_value) || is_array($search_filter_value))) {
        $connections_query->condition($search_filter_key, $search_filter_value);
      }
    }

    $connections_found = $connections_query->execute();
    $connections = array();
    if (!empty($connections_query)) {
      $controller = \Drupal::entityTypeManager()->getStorage($type . '_connection');
      $connections = $controller->loadMultiple($connections_found);
    }

    return $connections;
  }

  /**
   * Create a new connection for the PoolParty Semantic Connector.
   *
   * @param string $type
   *   The type of the connection to receive. Possible values: 'pp_server',
   *   'sonr_server' and 'sparql_endpoint'.
   * @param string $url
   *   The URL of the connection.
   * @param string $title
   *   The title of the connection.
   * @param array $credentials
   *   The credentials required for the connection in the format
   *   "username:password" if required.
   * @param array $config
   *   The config of the Semantic Connector Connection as an array.
   * @param int $fixed_id
   *   A fixed connection id to use instead of the one with the given $type and
   *   $url.
   *   WARNING: Using an ID that does not exist will result in an error.
   *
   * @return SemanticConnectorConnection
   *   The connection object, depending on the provided $type.
   */
  public static function createConnection($type, $url, $title, array $credentials = array('username' => '', 'password' => ''), array $config = array(), $fixed_id = 0) {
    $connection = NULL;
    $allowed_types = array('pp_server', 'sparql_endpoint');

    if (!in_array($type, $allowed_types)) {
      \Drupal::messenger()->addMessage(t('The type (%type) of the connection %title is wrong.', array('%type' => $type, '%title' => $title)), 'error');
      return NULL;
    }

    // Remove trailing slashes from the URL.
    $url = rtrim($url,"/");

    if ($fixed_id <= 0) {
      $query = \Drupal::entityQuery($type . '_connection');
      $old_connection_ids = $query->condition('url', $url)->execute();
      $old_connection_id = reset($old_connection_ids);
    }
    else {
      $old_connection_id = $fixed_id;
    }

    // If there is a connection available with the url, load it.
    if ($old_connection_id !== FALSE) {
      switch ($type) {
        case 'pp_server':
          $connection = SemanticConnectorPPServerConnection::load($old_connection_id);
          break;

        case 'sparql_endpoint':
          $connection = SemanticConnectorSparqlEndpointConnection::load($old_connection_id);
          break;
      }

      // If there already is a connection available, change if data has changed.
      $has_changed = FALSE;
      /** @var SemanticConnectorConnection $connection */
      if ($connection->getTitle() != $title) {
        $connection->setTitle($title);
        $has_changed = TRUE;
      }
      if ($connection->getUrl() != $url) {
        $connection->setUrl($url);
        $has_changed = TRUE;
      }
      if ($connection->getCredentials() != $credentials) {
        $connection->setCredentials($credentials);
        $has_changed = TRUE;
      }
      if (!empty($config) && $connection->getConfig() != $config) {
        $connection->setConfig(array_merge($connection->getConfig(), $config));
        $has_changed = TRUE;
      }

      // Save the connection if its data has changed.
      if ($has_changed) {
        $connection->save();
      }
    }
    // Data was not found in the DB --> Really create a new Connection.
    else {
      switch ($type) {
        case 'pp_server':
          $connection = SemanticConnectorPPServerConnection::create();
          break;

        case 'sparql_endpoint':
          $connection = SemanticConnectorSparqlEndpointConnection::create();
          break;
      }

      // Set the ID.
      $connection->set('id', self::createUniqueEntityMachineName($type . '_connection', $title));

      // Set all the required variables and save the connection.
      $connection->setTitle($title);
      $connection->setUrl($url);
      $connection->setCredentials($credentials);
      $connection->setConfig(array_merge($connection->getDefaultConfig(), $config));
      $connection->save();
    }

    return $connection;
  }

  /**
   * Delete one or multiple Semantic Connector connections.
   *
   * TODO: Remove all the sparql endpoints related to the Semantic Connector connections.
   *
   * @param string $type
   *   The type of the connections to receive. Possible values: 'pp_server'
   *   and 'sparql_endpoint'.
   * @param array $connection_ids
   *   A single connection_id or an array of connection_ids to remove.
   */
  public static function deleteConnections($type, array $connection_ids) {
    $controller = \Drupal::entityTypeManager()->getStorage($type . '_connection');
    $entities = $controller->loadMultiple($connection_ids);
    $controller->delete($entities);
  }

  /**
   * Check what Semantic Connector connections are used by which module.
   *
   * @param array $modules_to_check
   *   An array of module keys to check for connections.
   *
   * @return array
   *   Associative array of connections usages, categorized by connection_id and
   *   then by module_key.
   */
  public static function checkConnectionUsage(array $modules_to_check = array(
    'pp_taxonomy_manager',
    'powertagging',
    'smart_glossary',
    'pp_graphsearch',
  )) {
    $connections_used = array();

    foreach ($modules_to_check as $module_key) {
      if (\Drupal::moduleHandler()->moduleExists($module_key)) {
        switch ($module_key) {
          case 'powertagging':
            /** @var PowerTaggingConfig $config */
            foreach (PowerTaggingConfig::loadMultiple() as $config) {
              if (!isset($connections_used[$config->getConnectionId()])) {
                $connections_used[$config->getConnectionId()] = array();;
              }
              if (!isset($connections_used[$config->getConnectionId()][$module_key])) {
                $connections_used[$config->getConnectionId()][$module_key] = array();
              }
              $connections_used[$config->getConnectionId()][$module_key][] = array(
                'id' => $config->id(),
                'title' => $config->getTitle(),
                'project_id' => $config->getProjectId(),
              );
            }
            break;

          case 'smart_glossary':
            /** @var SmartGlossaryConfig $config */
            foreach (SmartGlossaryConfig::loadMultiple() as $config) {
              if (!isset($connections_used[$config->getConnectionID()])) {
                $connections_used[$config->getConnectionID()] = array();;
              }
              if (!isset($connections_used[$config->getConnectionID()][$module_key])) {
                $connections_used[$config->getConnectionID()][$module_key] = array();
              }
              $connections_used[$config->getConnectionID()][$module_key][] = array(
                'id' => $config->id(),
                'title' => $config->getTitle(),
              );
            }
            break;

          case 'pp_graphsearch':
            /** @var PPGraphSearchConfig $config */
            foreach (PPGraphSearchConfig::loadMultiple() as $config) {
              $connection_id = $config->getConnectionId();
              if (!isset($connections_used[$connection_id])) {
                $connections_used[$connection_id] = array();;
              }
              if (!isset($connections_used[$connection_id][$module_key])) {
                $connections_used[$connection_id][$module_key] = array();
              }
              $connections_used[$connection_id][$module_key][] = array(
                'id' => $config->id(),
                'title' => $config->getTitle(),
                'project_id' => $config->getSearchSpaceId(),
              );
            }
            break;

          case 'pp_taxonomy_manager':
            /** @var PPTaxonomyManagerConfig $config */
            foreach (PPTaxonomyManagerConfig::loadMultiple() as $config) {
              $settings = $config->getConfig();
              $connection_id = $config->getConnectionId();
              if (!isset($connections_used[$connection_id])) {
                $connections_used[$connection_id][$module_key] = array();
              }
              $connections_used[$connection_id][$module_key][] = array(
                'id' => $config->id(),
                'title' => $config->getTitle(),
                'root_level' => $settings['root_level'],
                'project_id' => $config->getProjectId(),
                'project_ids' => $settings['taxonomies'],
              );
            }
            break;
        }
      }
    }

    return $connections_used;
  }

  /**
   * Theme buttons to edit or delete a Semantic Connector connection.
   *
   * @param SemanticConnectorConnection $connection
   *   The Semantic Connector connection to theme the buttons for.
   * @param bool $can_be_deleted
   *   Whether a delete-button should be added or not.
   *
   * @return string
   *   The rendered HTML.
   */
  public static function themeConnectionButtons(SemanticConnectorConnection $connection, $can_be_deleted = FALSE) {
    $type = $connection->getType();
    $output = '<div class="semantic-connector-connection-buttons">';

    // Edit-button.
    $output .= Link::fromTextAndUrl(t('Edit'), Url::fromRoute('entity.' . $type . '_connection.edit_form', array($type . '_connection' => $connection->getId()), array('attributes' => array('class' => array('semantic-connector-connection-buttons-edit')))))->toString();

    // Delete button.
    if ($can_be_deleted) {
      $output .= '|' . Link::fromTextAndUrl(t('Delete'), Url::fromRoute('entity.' . $type . '_connection.delete_form', array($type . '_connection' => $connection->getId()), array('attributes' => array('class' => array('semantic-connector-connection-buttons-delete')))))->toString();
    }

    // Refresh projects button.
    if ($type == 'pp_server') {
      $output .= '|' . Link::fromTextAndUrl(t('Refresh server details'), Url::fromRoute('entity.pp_server_connection.refresh', array('connection' => $connection->getId()), array('attributes' => array('class' => array('semantic-connector-connection-buttons-refresh')))))->toString();
    }

    $server_config = $connection->getConfig();
    if (isset($server_config['version'])) {
      $output .= '|<span class="semantic-connector-connection-version">Version: ' . $server_config['version'] . '</span>';
    }

    // Get license information.
    if ($connection->getType() == 'pp_server') {
      $license_information = $connection->getApi('PPT')->getLicense();
      $license_classes = self::checkPoolpartyLicenses($connection, $license_information, TRUE);
      $output .= '|<span class="semantic-connector-connection-license ' . implode(' ', $license_classes) . '">License valid until: ' . (isset($license_information['expiryDateInMillis']) ? date('j. M Y', $license_information['expiryDateInMillis'] / 1000) : '-') . '</span>';
    }

    $output .= '</div>';
    return $output;
  }

  /**
   * Theme concepts with all their possible destinations.
   *
   * @param array $concepts
   *   An associative array containing following keys:
   *   - "html" --> The HTML of a concept, that will be used as the link text
   *   - "uri" --> The URI of the concept; if the URI is left empty, this item
   *     will be handled as a free term (no linking, but still added to the list)
   *   - "alt_labels" (optional) --> The alt labels to be added into the hidden box
   *   - "hidden_labels" (optional) --> The hidden labels to be added into the hidden box
   * @param int $connection_id
   *   The ID of the Semantic Connector connection.
   * @param string $project_id
   *   The ID of the project this concept is from.
   * @param string $separator
   *   If more than one concept is given, the list of concepts will will be
   *   separated with this string.
   * @param array $ignore_destinations
   *   An array of destination IDs, which should not be displayed.
   *
   * @return string
   *   The themed list of concepts.
   */
  public static function themeConcepts(array $concepts, $connection_id, $project_id, $separator = ', ', array $ignore_destinations = array()) {
    global $base_path;
    $themed_items = array();
    $destinations = self::getDestinations();

    if (!empty($concepts)) {
      // Get all URI --> tid connections to avoid lots of database requests.
      $uri_tid_mapping = array();
      if ($destinations['taxonomy_term_detail_page']['use'] && !in_array('taxonomy_term_detail_page', $ignore_destinations)) {
        $query = \Drupal::database()->select('taxonomy_term__field_uri', 'u');
        $query->fields('u', array('field_uri_uri', 'entity_id'));
        $uri_tid_mapping = $query->execute()->fetchAllKeyed();
      }
      $pp_server_connection = SemanticConnector::getConnection('pp_server', $connection_id);

      $smart_glossary_destinations = array();
      if (isset($destinations['smart_glossary_detail_page']) && $destinations['smart_glossary_detail_page']['use'] && !in_array('smart_glossary_detail_page', $ignore_destinations)) {

        $server_config = $pp_server_connection->getConfig();
        if (isset($server_config['projects']) && !empty($server_config['projects'])) {
          foreach ($server_config['projects'] as $project) {
            if ($project['id'] == $project_id) {
              if (isset($project['sparql_endpoint_url'])) {
                // Get all IDs of SPARQL endpoint connections matching the
                // project's sparql endpoints url.
                $connection_query = \Drupal::entityQuery('sparql_endpoint_connection');
                $connection_query->condition('url', $project['sparql_endpoint_url']);
                $sparql_endpoint_connection_ids = $connection_query->execute();
                if (!empty($sparql_endpoint_connection_ids)) {
                  // Load all Smart Glossary configurations for the connection.
                  $smart_glossary_configs = \Drupal::entityTypeManager()
                    ->getStorage('smart_glossary')
                    ->loadByProperties(array('connection_id' => array_keys($sparql_endpoint_connection_ids)));

                  /** @var SmartGlossaryConfig $smart_glossary_config */
                  foreach ($smart_glossary_configs as $smart_glossary_config) {
                    $language_mapping = $smart_glossary_config->getLanguageMapping();
                    $advanced_settings = $smart_glossary_config->getAdvancedSettings();
                    //@todo: add multilanguage support.
                    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
                    if (isset($language_mapping[$default_language]) && !empty($language_mapping[$default_language]['glossary_languages'][0]) && (!isset($advanced_settings['semantic_connection']['show_in_destinations']) || $advanced_settings['semantic_connection']['show_in_destinations'])) {
                      $smart_glossary_destinations[$smart_glossary_config->getBasePath() . '/' . $language_mapping[$default_language]['glossary_languages'][0]] = $smart_glossary_config->getTitle();
                    }
                  }
                }
              }
              break;
            }
          }
        }
      }

      $pp_graphsearch_destinations = array();
      if (isset($destinations['pp_graphsearch']) && $destinations['pp_graphsearch']['use'] && !in_array('pp_graphsearch', $ignore_destinations)) {

        $connection_config = $pp_server_connection->getConfig();
        if (isset($connection_config["graphsearch_configuration"]) && !empty($connection_config["graphsearch_configuration"])) {
          if (isset($connection_config["graphsearch_configuration"]['projects'][$project_id])) {
            $search_space_ids = array_keys($connection_config["graphsearch_configuration"]['projects'][$project_id]['search_spaces']);

            // Get all block paths of sOnr webmining blocks, which use the given
            // connection ID and project ID.
            $pp_graphsearch_configs = \Drupal::entityTypeManager()
              ->getStorage('pp_graphsearch')
              ->loadByProperties(array(
                'connection_id' => $connection_id,
                'search_space_id' => $search_space_ids
              ));

            if (!empty($pp_graphsearch_configs)) {
              /** @var PPGraphSearchConfig $pp_graphsearch_config */
              foreach ($pp_graphsearch_configs as $pp_graphsearch_config) {
                $advanced_settings = $pp_graphsearch_config->getConfig();
                if (!isset($advanced_settings['semantic_connection']['show_in_destinations']) || $advanced_settings['semantic_connection']['show_in_destinations']) {
                  // Use the first concrete path of the block.
                  $pp_graphsearch = new PPGraphSearch($pp_graphsearch_config);
                  $pp_graphsearch_block_path = $pp_graphsearch->getBlockPath();
                  if (!empty($pp_graphsearch_block_path)) {
                    $pp_graphsearch_destinations[($pp_graphsearch_block_path == '<front>' ? '' : $pp_graphsearch_block_path)] = $pp_graphsearch_config->getTitle();
                  }
                }
              }
            }
          }
        }
      }

      foreach ($concepts as $concept) {
        if (!isset($concept['uri']) | !isset($concept['html'])) {
          continue;
        }

        // Free terms.
        if (empty($concept['uri'])) {
          $themed_items[] = $concept['html'];
        }
        // Real concepts.
        else {
          $destination_links = array();
          // Destinations are ordered by weight already, so we don't have to check
          // this property here.
          foreach ($destinations as $destination_id => $destination) {
            if ($destination['use']) {
              switch ($destination_id) {
                case 'taxonomy_term_detail_page':
                  if (isset($uri_tid_mapping[$concept['uri']])) {
                    $destination_links['taxonomy/term/' . $uri_tid_mapping[$concept['uri']]] = $destination['list_title'];
                  }
                  break;
                case 'smart_glossary_detail_page':
                  foreach ($smart_glossary_destinations as $smart_glossary_path => $smart_glossary_title) {
                    $destination_links[$smart_glossary_path . '/concept?uri=' . $concept['uri']] = $destination['list_title'] . (count($smart_glossary_destinations) > 1 ? ' (' . $smart_glossary_title . ')' : '');
                  }
                  break;
                case 'pp_graphsearch':
                  foreach ($pp_graphsearch_destinations as $pp_graphsearch_path => $pp_graphsearch_title) {
                    $destination_links[$pp_graphsearch_path . '?uri=' . $concept['uri']] = $destination['list_title'] . (count($pp_graphsearch_destinations) > 1 ? ' (' . $pp_graphsearch_title . ')' : '');
                  }
                  break;
              }
            }
          }

          // Theme the item.
          $themed_item_content = '';
          if (empty($destination_links)) {
            $themed_item_content .= $concept['html'];
          }
          else {
            $themed_item_content .= '<div class="semantic-connector-concept"><ul class="semantic-connector-concept-menu"><li><a class="semantic-connector-concept-link" href="' . $base_path . key($destination_links) . '">' . $concept['html'] . '</a>';
            if (count($destination_links) > 1) {
              $themed_item_content .= '<ul class="semantic-connector-concept-destination-links">';
              foreach ($destination_links as $destination_link_path => $destination_link_label) {
                // Remove initial slash in the path if available.
                if (substr($destination_link_path, 0, 1) == '/') {
                  $destination_link_path = substr($destination_link_path, 1);
                }
                $themed_item_content .= '<li class="semantic-connector-concept-destination-link"><a href="' . $base_path . $destination_link_path . '">' . $destination_link_label . '</a></li>';
              }
              $themed_item_content .= '</ul>';
            }
            $hidden_box_content = '';
            if (isset($concept['alt_labels']) && !empty($concept['alt_labels'])) {
              $hidden_box_content .= implode(', ', $concept['alt_labels']);
            }
            if (isset($concept['hidden_labels']) && !empty($concept['hidden_labels'])) {
              $hidden_box_content .= (!empty($hidden_box_content) ? ',' : '') . implode(', ', $concept['hidden_labels']);
            }
            $themed_item_content .= '</li></ul>' . (!empty($hidden_box_content) ? '<div class="semantic-connector-concept-hidden-box">' . $hidden_box_content . '</div>' : '') . '</div>';
          }
          $themed_items[] = $themed_item_content;
        }
      }
    }

    return implode($separator, $themed_items);
  }

  /**
   * Get an array of available destinations to go to from a concept link.
   *
   * @return array
   *   The array of destinations keyed by the destination-id, each one is an array
   *   with following keys:
   *   - "weight" --> The weight that defines the order of this destination in the
   *     list of available destinations.
   *   - "label" --> A label describing this destination.
   *   - "list_title" --> The title of the destination for the users in the list
   *     of available destinations.
   *   - "use" --> TRUE if this destination has to be used, FALSE if not.
   */
  public static function getDestinations() {
    // An array of available destinations with their default values.
    $available_destinations = array(
      'taxonomy_term_detail_page' => array(
        'weight' => 1,
        'label' => t('Taxonomy Term Detail Page'),
        'list_title' => 'Taxonomy Term Detail Page',
        'use' => FALSE,
      ),
    );
    if (\Drupal::moduleHandler()->moduleExists('smart_glossary')) {
      $available_destinations['smart_glossary_detail_page'] = array(
        'weight' => 0,
        'label' => t('Smart Glossary Detail Page'),
        'list_title' => 'Smart Glossary Detail Page',
        'use' => FALSE,
      );
    }
    if (\Drupal::moduleHandler()->moduleExists('pp_graphsearch')) {
      $available_destinations['pp_graphsearch'] = array(
        'weight' => 2,
        'label' => t('PoolParty GraphSearch Page'),
        'list_title' => 'PoolParty GraphSearch Page',
        'use' => FALSE,
      );
    }

    // Replace the default values with actual saved values.
    $term_destination_options = \Drupal::config('semantic_connector.settings')->get('term_click_destinations');
    if (!is_null($term_destination_options)) {
      foreach ($term_destination_options as $destination_id => $destination) {
        if (isset($available_destinations[$destination_id])) {
          foreach (array_keys($available_destinations[$destination_id]) as $destination_property) {
            if (isset($destination[$destination_property])) {
              $available_destinations[$destination_id][$destination_property] = $destination[$destination_property];
            }
          }
        }
      }
    }

    // Order the destinations by weight.
    uasort($available_destinations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $available_destinations;
  }

  /**
   * Get detailed information about SPARQL endpoints from a PoolParty server.
   *
   * @param string $connection_id
   *   The ID of the SPARQL endpoint connection
   *
   * @return array|bool
   *   Array of information found found for this SPARQL endpoint containing
   *   following keys:
   *   - "pp_connection_id" --> The ID of the corresponding PoolParty server
   *     connection containing the SPARQL endpoint.
   *   - "project_id" --> The ID of the project using the SPARQL endpoint.
   *   or FALSE if no information was found or if this connection does not exist.
   */
  public static function getSparqlConnectionDetails($connection_id) {
    $sparql_connection = SemanticConnector::getConnection('sparql_endpoint', $connection_id);
    if (!is_null($sparql_connection)) {
      $pp_server_connections = SemanticConnector::getConnectionsByType('pp_server');
      foreach ($pp_server_connections as $pp_server_connection) {
        $server_config = $pp_server_connection->getConfig();
        if (isset($server_config['projects']) && !empty($server_config['projects'])) {
          foreach ($server_config['projects'] as $project) {
            if (isset($project->sparql_endpoint_url) && $project->sparql_endpoint_url == $sparql_connection->getUrl()) {
              return array(
                'pp_connection_id' => $pp_server_connection->getId(),
                'project_id' => $project->id,
              );
            }
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Create a unique machine name for an entity based on a title.
   *
   * @param string $entity_id
   *   The ID of the entity to get the machine name for
   * @param string $title
   *   The title to build the machine name with.
   *
   * @return string
   *   The machine name
   */
  public static function createUniqueEntityMachineName($entity_id, $title) {
    // Title and entity ID may not be empty.
    if (empty($entity_id) || empty($title)) {
      return NULL;
    }

    $machine_name = \Drupal::transliteration()
      ->transliterate($title, LanguageInterface::LANGCODE_DEFAULT, '_');
    $machine_name = str_replace(' ', '_', Unicode::strtolower($machine_name));

    $entity_ids = \Drupal::entityQuery($entity_id)
      ->condition('id', $machine_name, 'STARTS_WITH')
      ->execute();

    // The machine name is already in use, check for a new one.
    if (!empty($entity_ids) && in_array($machine_name, $entity_ids)) {
      $machine_name_count = 1;
      while (TRUE) {
        $new_machine_name = $machine_name . '_' . $machine_name_count;
        if (!in_array($new_machine_name, $entity_ids)) {
          return $new_machine_name;
        }
        $machine_name_count++;
      }
    }

    return $machine_name;
  }

  /**
   * Get the configuration for the global notifications.
   *
   * @return array
   *   An associative array of notification settings.
   */
  public static function getGlobalNotificationConfig() {
    return \Drupal::config('semantic_connector.settings')->get('notifications');
  }

  /**
   * Get the configuration for the global notifications.
   *
   * @return array
   *   An array of associative action arrays.
   *   Every action has following properties:
   *   - id: string that gets used as the key of the action
   *   - title
   *   - description
   *   - default_value: boolean default value
   *   - callback: the function to call to check for the notification
   */
  public static function getGlobalNotificationActions() {
    $actions = array();

    $default_action = array(
      'id' => '',
      'title' => '',
      'description' => '',
      'default_value' => TRUE,
      'callback' => '',
    );

    foreach (\Drupal::moduleHandler()->getImplementations('semantic_connector_global_notification_actions') as $module) {
      $module_actions = \Drupal::moduleHandler()->invoke($module, 'semantic_connector_global_notification_actions');
      foreach ($module_actions as $module_action) {
        $module_action = $module_action + $default_action;
        // At least ID, title and callback have to be given.
        if (!empty($module_action['id']) && !empty($module_action['title']) && !empty($module_action['callback'])) {
          $actions[] = $module_action;
        }
      }
    }
    return $actions;
  }

  /**
   * Check all global notifications.
   *
   * @param bool $force_check
   *   Check for notifications, even if interval is not yet over.
   * @param bool $send_mail
   *   TRUE if mails should be sent out (if mail addresses are provided in the
   *   notification settings.
   *
   * @return string[]
   *   Array of notification strings.
   */
  public static function checkGlobalNotifications($force_check = FALSE, $send_mail = FALSE) {
    $notifications = array();
    $notification_config = self::getGlobalNotificationConfig();
    $notification_config_update_required = FALSE;

    if ($notification_config['enabled']) {
      $settings = \Drupal::configFactory()->getEditable('semantic_connector.settings');
      $last_notification_check = $settings->get('global_notification_last_check');
      // Find out if a check is already required.
      if ((time() - $last_notification_check) >= $notification_config['interval'] || $force_check) {
        $actions = self::getGlobalNotificationActions();
        foreach ($actions as $action) {
          // Use the default value in case there is no value yet.
          if (!isset($notification_config['actions'][$action['id']])) {
            $notification_config['actions'][$action['id']] = $action['default_value'];
            $notification_config_update_required = TRUE;
          }

          // Find out if this specfic check has to be done.
          if ($notification_config['actions'][$action['id']]) {
            $action_notifications = call_user_func($action['callback']);
            $notifications = array_merge($notifications, $action_notifications);
          }
        }

        // Update the notifications and update the notificiation config if required.
        $settings->set('global_notifications', $notifications);
        if ($notification_config_update_required) {
          $settings->set('notifications', $notification_config);
        }

        // Send mails if required.
        if (!empty($notifications) && $send_mail && !empty($notification_config['mail_to'])) {
          $params = array(
            'notifications' => $notifications,
          );
          $mailManager = \Drupal::service('plugin.manager.mail');
          $mailManager->mail('semantic_connector', 'global_notifications', $notification_config['mail_to'], \Drupal::languageManager()->getDefaultLanguage()->getId(), $params, NULL, TRUE);
        }

        // Set the current timestamp as last check and update the notifications.
        $settings->set('global_notification_last_check', time());
        $settings->save();
      }
      // If no check is required use the existing notifications.
      else {
        $notifications = \Drupal::config('semantic_connector.settings')->get('global_notifications');
      }
    }

    return $notifications;
  }

  /**
   * Get the search spaces of a GraphSearch config.
   *
   * @param array $graphsearch_config
   *   The GraphSearch configuration including the search space data.
   * @param string $search_space_id_filter
   *   Optional; The ID of the search space.
   * @param string $project_id_filter
   *   Optional; The ID of the search PoolParty project used in the search space.
   * @param string $language_filter
   *   Optional; The language of the search space.
   *
   * @return array
   *   An array of search spaces by their ID, each item contains following keys:
   *   - "id": The ID of the search space
   *   - "name": The name of the search space
   *   - "language": The language used in the search space
   *   - "project_ids": An array of project ID string the search space contains
   */
  public static function getGraphSearchSearchSpaces(array $graphsearch_config, $search_space_id_filter = '', $project_id_filter = '', $language_filter = '') {
    $search_spaces = array();

    if (isset($graphsearch_config['projects'])) {
      // Create a list of all search spaces first.
      foreach ($graphsearch_config['projects'] as $graphsearch_project) {
        foreach ($graphsearch_project['search_spaces'] as $search_space) {
          if (!isset($search_spaces[$search_space['id']])) {
            $search_spaces[$search_space['id']] = $search_space;
            $search_spaces[$search_space['id']]['project_ids'] = array();
          }
          $search_spaces[$search_space['id']]['project_ids'][] = $graphsearch_project['id'];
        }
      }

      // Remove wrong search spaces then.
      foreach ($search_spaces as $search_space_id => $search_space) {
        if (
          (!empty($search_space_id_filter) && $search_space_id_filter != $search_space_id)
          || (!empty($project_id_filter) && !in_array($project_id_filter, $search_space['project_ids']))
          || (!empty($language_filter) && $language_filter != $search_space['language'])
        ) {
          unset($search_spaces[$search_space_id]);
        }
      }
    }

    return $search_spaces;
  }

  /**
   * Check if any extraction model has to be refreshed.
   *
   * @param SemanticConnectorPPServerConnection $connection
   *   Optional; A specific PoolParty Server Connection to check for a valid
   *   license.
   * @param array $license
   *   Optional; A fixed license to check against instead of fetching the actual
   *   license used by the server.
   * @param bool $class_only
   *   If set to TRUE, instead of a message only "warning" or "error" will be
   *   returned, depending on the date of expiration.
   *
   * @return string[]
   *   Array of notification strings.
   */
  public static function checkPoolpartyLicenses($connection = NULL, $license = NULL, $class_only = FALSE) {
    $notifications = array();

    if (!is_null($connection)) {
      $pp_server_connections = array($connection);
    }
    else {
      $pp_server_connections = SemanticConnector::getConnectionsByType('pp_server');
    }

    /** @var SemanticConnectorPPServerConnection $pp_server_connection */
    foreach ($pp_server_connections as $pp_server_connection) {
      // Load the license information if required.
      if (!is_null($license)) {
        $license_information = $license;
      }
      else {
        $license_information = $pp_server_connection->getApi('PPT')->getLicense();
      }

      if (isset($license_information['expiryDateInMillis'])) {
        $current_time = time();
        // License already expired.
        if (($license_information['expiryDateInMillis'] / 1000) <= $current_time) {
          if (!$class_only) {
            // Add the notification.
            $notifications[] = t('The PoolParty license for connection "%connection" is outdated.', array('%connection' => $pp_server_connection->getTitle()));
          }
          else {
            $notifications[] = 'license-expired';
          }
        }
        // License expires in the next 14 days.
        elseif (($license_information['expiryDateInMillis'] / 1000) <= ($current_time + 1209600)) {
          if (!$class_only) {
            // Add the notification.
            $notifications[] = t('The PoolParty license for connection "%connection" is about to run out on %expiration.', array('%connection' => $pp_server_connection->getTitle(), '%expiration' => date('j. M Y', $license_information['expiryDateInMillis'] / 1000)));
          }
          else {
            $notifications[] = 'license-almost-expired';
          }
        }
      }
    }

    return $notifications;
  }

  /**
   * Find out if the Visual Mapper exists.
   *
   * @return bool
   *   TRUE if the Visual Mapper exists, FALSE if not
   *
   * @deprecated No longer used since the VisualMapper library was included in
   *   the Semantic Connector in version 8.x-1.0. Use visualMapperUsable()
   *   instead.
   */
  public static function visualMapperExists() {
    return self::visualMapperUsable();
  }

  /**
   * Find out if the Visual Mapper can be used.
   *
   * @return bool
   *   TRUE if the Visual Mapper can be used, FALSE if not
   */
  public static function visualMapperUsable() {
    return file_exists(\Drupal::service('file_system')->realpath('libraries/d3js/d3.min.js'));
  }
}