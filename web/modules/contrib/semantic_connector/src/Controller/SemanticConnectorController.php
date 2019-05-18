<?php

/**
 * @file
 * Contains \Drupal\semantic_connector\Controller\SemanticConnectorController class.
 */

namespace Drupal\semantic_connector\Controller;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\pp_graphsearch_similar\Entity\PPGraphSearchSimilarConfig;
use Drupal\semantic_connector\Entity\SemanticConnectorConnection;
use Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection;
use Drupal\semantic_connector\Entity\SemanticConnectorSparqlEndpointConnection;
use Drupal\semantic_connector\SemanticConnector;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for the Semantic Connector module.
 */
class SemanticConnectorController extends ControllerBase {
  /**
   * Returns markup for our custom page.
   */
  public function overview() {
    $output = array();

    // Create a list of all "Semantic Drupal" modules.
    $output['modules_title'] = array(
      '#type' => 'markup',
      '#markup' => '<h3 class="semantic-connector-table-title">' . t('Semantic Drupal modules') . '</h3>',
    );

    $installed_modules = array_keys(\Drupal::moduleHandler()->getModuleList());
    $semantic_modules = array(
      'pp_taxonomy_manager' => array(
        'title' => 'PoolParty Taxonomy Manager',
        'configuration_route' => 'entity.pp_taxonomy_manager.collection',
      ),
      'powertagging' => array(
        'title' => 'PowerTagging',
        'configuration_route' => 'entity.powertagging.collection',
      ),
      'pp_graphsearch' => array(
        'title' => 'PoolParty GraphSearch',
        'configuration_route' => 'entity.pp_graphsearch.collection',
      ),
      'smart_glossary' => array(
        'title' => 'Smart Glossary',
        'configuration_route' => 'entity.smart_glossary.collection',
      ),
    );

    $module_rows = array();
    $installed_semantic_modules = array();
    foreach ($semantic_modules as $module_key => $module_info) {
      $installed = in_array($module_key, $installed_modules);
      $module_rows[] = array(
        $module_info['title'],
        ($installed ? 'installed' : Link::fromTextAndUrl(t('Download'), Url::fromUri('http://www.drupal.org/project/' . $module_key))->toString()),
        ($installed ? Link::fromTextAndUrl(t('Configure'), Url::fromRoute($module_info['configuration_route']))->toString() : ''),
      );

      // Create a list of connections used by module and connection-id.
      if ($installed) {
        $installed_semantic_modules[] = $module_key;
      }
    }

    $output['modules'] = array(
      '#theme' => 'table',
      '#header' => array(t('Module'), t('Installed'), t('Configuration')),
      '#rows' => $module_rows,
      '#caption' => NULL,
      '#colgroups' => array(),
      '#sticky' => FALSE,
      '#empty' => '',
    );

    // Find out what connections are used by the installed semantic modules.
    $connections_used = SemanticConnector::checkConnectionUsage($installed_semantic_modules);
    $pp_server_connections = SemanticConnector::getConnectionsByType('pp_server');

    // Build an array of existing connections using a SPARQL endpoint.
    $sparql_endpoint_connections = SemanticConnector::getConnectionsByType('sparql_endpoint');
    $sparql_endpoint_connections_assoc = array();
    /** @var SemanticConnectorSparqlEndpointConnection $sparql_endpoint_connection */
    foreach ($sparql_endpoint_connections as $sparql_endpoint_connection) {
      $sparql_endpoint_connections_assoc[$sparql_endpoint_connection->getUrl()] = $sparql_endpoint_connection;
    }
    $sparql_endpoint_connections = $sparql_endpoint_connections_assoc;
    unset($sparql_endpoint_connections_assoc);

    // Get all SeeAlso widgets if available.
    $pp_graphsearch_similar_configs = array();
    if (\Drupal::moduleHandler()->moduleExists('pp_graphsearch_similar')) {
      $similar_configs = PPGraphSearchSimilarConfig::loadMultiple();
      /** @var PPGraphSearchSimilarConfig $similar_config */
      foreach ($similar_configs as $similar_config) {
        $key = $similar_config->getConnectionId() . '|' . $similar_config->getSearchSpaceId();
        $pp_graphsearch_similar_configs[$key][] = $similar_config;
      }
    }

    // List the PoolParty server connections.
    foreach ($pp_server_connections as $pp_server_connection) {
      /** @var SemanticConnectorPPServerConnection $pp_server_connection */
      $server_id = $pp_server_connection->id();
      $server_config = $pp_server_connection->getConfig();
      $server_ppx_projects = $pp_server_connection->getApi('PPX')->getProjects();

      $output['server_anchor_' . $server_id] = array(
        '#markup' => '<a id="pp-server-' . $server_id . '"></a>',
      );
      $server_title = '<h3 class="semantic-connector-table-title">';
      // Check the PoolParty server version if required.
      if (\Drupal::config('semantic_connector.settings')->get('version_checking')) {
        $api_version_info = $pp_server_connection->getVersionInfo('PPX');
        if (version_compare($api_version_info['installed_version'], $api_version_info['latest_version'], '<')) {
          $server_title .= '<div class="messages warning">' . t('The installed PoolParty server version is not up to date. You are currently running version %installedversion, upgrade to version %latestversion to enjoy the new features.', array('%installedversion' => $api_version_info['installed_version'], '%latestversion' => $api_version_info['latest_version'])) . '</div>';
        }
      }
      $server_title .= '<div class="semantic-connector-led" data-server-id="' . $server_id . '" data-server-type="pp-server" title="' . t('Checking service') . '"></div>';
      $server_title .= t('PoolParty server "%pptitle"', array('%pptitle' => $pp_server_connection->getTitle()));
      $server_title .= '<span class="semantic-connector-url">' . Link::fromTextAndUrl($pp_server_connection->getUrl(), Url::fromUri($pp_server_connection->getUrl() . '/PoolParty', array('attributes' => array('target' => '_blank'))))->toString() . '</span></h3>';
      $output['server_title_' . $server_id] = array(
        '#markup' => $server_title,
      );

      $output['server_buttons_' . $server_id] =  array(
        '#markup' => SemanticConnector::themeConnectionButtons($pp_server_connection, !isset($connections_used[$server_id])),
      );

      $project_rows = array();
      if (isset($server_config['projects']) && !empty($server_config['projects'])) {
        foreach ($server_config['projects'] as $project) {
          $project_row = array($project['title']);

          foreach ($installed_semantic_modules as $semantic_module_key) {
            switch ($semantic_module_key) {

              // PoolParty Taxonomy Manager cell content.
              case 'pp_taxonomy_manager':
                $project_taxonomy_manager_content = '';
                if (isset($connections_used[$server_id]) && isset($connections_used[$server_id]['pp_taxonomy_manager'])) {
                  foreach ($connections_used[$server_id]['pp_taxonomy_manager'] as $pp_taxonomy_manager_use) {
                    if ($pp_taxonomy_manager_use['root_level'] == 'project') {
                      if (in_array($project['id'], $pp_taxonomy_manager_use['project_ids'])) {
                        $project_taxonomy_manager_content = Link::fromTextAndUrl($pp_taxonomy_manager_use['title'], Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $pp_taxonomy_manager_use['id']), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString() . '</li>';
                        break;
                      }
                    }
                    else {
                      if ($pp_taxonomy_manager_use['project_id'] == $project['id']) {
                        $project_taxonomy_manager_content = Link::fromTextAndUrl($pp_taxonomy_manager_use['title'], Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $pp_taxonomy_manager_use['id']), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString() . '</li>';
                        break;
                      }
                    }
                  }
                }
                if (empty($project_taxonomy_manager_content)) {
                  $project_taxonomy_manager_content = Link::fromTextAndUrl(t('Add new configuration'), Url::fromRoute('entity.pp_taxonomy_manager.fixed_connection_add_form', array('connection' => $server_id, 'project_id' => $project['id']), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString();
                }
                $project_row[] = new FormattableMarkup($project_taxonomy_manager_content, array());
                break;

              // PowerTagging cell content.
              case 'powertagging':
                // Check if the project is valid for PPX communication
                // (extraction model was already built).
                $project_is_valid = FALSE;
                foreach ($server_ppx_projects as $server_ppx_project) {
                  if ($server_ppx_project['uuid'] == $project['id']) {
                    $project_is_valid = TRUE;
                    break;
                  }
                }

                // Valid PPX project.
                if ($project_is_valid) {
                  $project_powertagging_content = '';
                  $powertagging_project_uses = array();
                  if (isset($connections_used[$server_id]) && isset($connections_used[$server_id]['powertagging'])) {
                    foreach ($connections_used[$server_id]['powertagging'] as $powertagging_use) {
                      // This PoolParty GraphSearch configuration uses the PoolParty GraphSearch server using
                      // this project on the current PP server.
                      if ($powertagging_use['project_id'] == $project['id']) {
                        $powertagging_project_uses[] = '<li>' . Link::fromTextAndUrl($powertagging_use['title'], Url::fromRoute('entity.powertagging.edit_config_form', array('powertagging' => $powertagging_use['id']), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString() . '</li>';
                      }
                    }
                  }
                  if (!empty($powertagging_project_uses)) {
                    $project_powertagging_content .= '<ul>' . implode('', $powertagging_project_uses) . '</ul>';
                  }
                  $project_powertagging_content .= '<div class="add-configuration">' . Link::fromTextAndUrl(t('Add new configuration'), Url::fromRoute('entity.powertagging.fixed_connection_add_form', array('connection' => $server_id, 'project_id' => $project['id']), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString() . '</div>';
                }
                // Project is not available for PowerTagging.
                else {
                  $project_powertagging_content = '<div class="semantic-connector-italic">' . t('not supported') . '</div>';
                }
                $project_row[] = new FormattableMarkup($project_powertagging_content, array());
                break;

              // $project_graphsearch_content cell content.
              case 'pp_graphsearch':
                $project_graphsearch_content = '';
                // A PoolParty GraphSearch server is available for this project on the current PP server.
                if (isset($server_config['graphsearch_configuration']) && !empty($server_config['graphsearch_configuration']) && isset($server_config['graphsearch_configuration']['projects'][$project['id']])) {
                  $pp_graphsearch_project_uses = array();
                  if (isset($connections_used[$server_id]) && isset($connections_used[$server_id]['pp_graphsearch'])) {
                    foreach ($connections_used[$server_id]['pp_graphsearch'] as $pp_graphsearch_use) {
                      // This PoolParty GraphSearch configuration uses the PoolParty GraphSearch server using
                      // this project on the current PP server.
                      if (isset($server_config['graphsearch_configuration']['projects'][$project['id']]['search_spaces'][$pp_graphsearch_use['project_id']])) {
                        $pp_graphsearch_project_uses[] = '<li>' . Link::fromTextAndUrl($pp_graphsearch_use['title'], Url::fromRoute('entity.pp_graphsearch.edit_config_form', array('pp_graphsearch' => $pp_graphsearch_use['id']), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString() . '</li>';
                      }
                    }
                  }
                  if (!empty($pp_graphsearch_project_uses)) {
                    $project_graphsearch_content .= '<ul>' . implode('', $pp_graphsearch_project_uses) . '</ul>';
                  }
                  $project_graphsearch_content .= '<div class="add-configuration">' . Link::fromTextAndUrl(t('Add new configuration'),  Url::fromRoute('entity.pp_graphsearch.fixed_connection_add_form', array('connection' => $server_id, 'project_id' => $project['id']), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString() . '</div>';

                  if (\Drupal::moduleHandler()->moduleExists('pp_graphsearch_similar')) {
                    $project_graphsearch_content .= '<hr>';
                    $similar_project_uses = array();

                    // Get all GraphSearch SeeAlso Engine configurations for
                    // search spaces available for the current project.
                    $project_conf = $server_config['graphsearch_configuration']['projects'][$project['id']];
                    if (isset($project_conf['search_spaces'])) {
                      foreach ($project_conf['search_spaces'] as $search_space) {
                        $key = $server_id . '|' . $search_space['id'];
                        if (isset($pp_graphsearch_similar_configs[$key])) {
                          foreach ($pp_graphsearch_similar_configs[$key] as $similar_config) {
                            $similar_project_uses[] = '<li>' . Link::fromTextAndUrl($similar_config->getTitle(), Url::fromRoute('entity.pp_graphsearch_similar.edit_config_form', array('pp_graphsearch_similar' => $similar_config->id()), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString() . '</li>';
                          }
                        }
                      }
                    }

                    if (!empty($similar_project_uses)) {
                      $project_graphsearch_content .= '<ul>' . implode('', $similar_project_uses) . '</ul>';
                    }
                    $project_graphsearch_content .= '<div class="add-configuration">' . Link::fromTextAndUrl(t('Add new SeeAlso widget'), Url::fromRoute('entity.pp_graphsearch_similar.fixed_connection_add_form', array('connection' => $server_id, 'project_id' => $project['id']), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString() . '</div>';
                  }
                }
                // There is no PoolParty GraphSearch server available for this project on the PP server.
                else {
                  $project_graphsearch_content .= '<div class="semantic-connector-italic">' . t('not supported') . '</div>';
                }
                $project_row[] = new FormattableMarkup($project_graphsearch_content, array());
                break;

              // Smart Glossary cell content.
              case 'smart_glossary':
                $project_sparql_content = '';
                if (isset($project['sparql_endpoint_url']) && isset($sparql_endpoint_connections[$project['sparql_endpoint_url']])) {
                  $sparql_endpoint_connection = $sparql_endpoint_connections[$project['sparql_endpoint_url']];
                  $smart_glossary_project_uses = array();
                  if (isset($connections_used[$sparql_endpoint_connection->id()]) && isset($connections_used[$sparql_endpoint_connection->id()]['smart_glossary'])) {
                    foreach ($connections_used[$sparql_endpoint_connection->id()]['smart_glossary'] as $smart_glossary_use) {
                      $smart_glossary_project_uses[] = '<li>' . Link::fromTextAndUrl($smart_glossary_use['title'], Url::fromRoute('entity.smart_glossary.edit_form', array('smart_glossary' => $smart_glossary_use['id']), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString() . '</li>';
                    }
                  }
                  if (!empty($smart_glossary_project_uses)) {
                    $project_sparql_content .= '<ul>' . implode('', $smart_glossary_project_uses) . '</ul>';
                  }
                  $project_sparql_content .= '<div class="add-configuration">' . Link::fromTextAndUrl(t('Add new configuration'), Url::fromRoute('entity.smart_glossary.fixed_connection_add_form', array('connection' => $sparql_endpoint_connection->id()), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString() . '</div>';
                  unset($sparql_endpoint_connections[$project['sparql_endpoint_url']]);
                }
                else {
                  $project_sparql_content .= '<div class="semantic-connector-italic">' . t('not supported') . ' (' . t('refresh PoolParty server details') . ')</div>';
                }
                $project_row[] = new FormattableMarkup($project_sparql_content, array());
                break;
            }
          }

          // Add the collected data for the project as a row.
          $project_rows[] = $project_row;
        }
      }

      $pp_table_headers = array(t('Projects'));
      foreach ($installed_semantic_modules as $semantic_module_key) {
        $pp_table_headers[] = $semantic_modules[$semantic_module_key]['title'];
      }
      $output['server_projects_' . $server_id] = array(
        '#theme' => 'table',
        '#header' => $pp_table_headers,
        '#rows' => $project_rows,
        '#sticky' => TRUE,
        '#empty' => t('This PoolParty server has no projects available for the configured user.'),
        '#attributes' => array('class' => array('pp-server-projects-table', 'semantic-connector-tablesorter')),
      );
    }

    // Add all the custom SPARQL endpoints.
    if (!empty($sparql_endpoint_connections)) {
      $output['sparql_endpoints_title'] = array(
        '#markup' => '<h3 class="semantic-connector-table-title">' . t('Custom SPARQL endpoints') . '</h3>',
      );

      $sparql_endpoint_header = array();
      $sparql_endpoint_rows = array();

      foreach ($sparql_endpoint_connections as $sparql_endpoint_connection) {
        $sparql_connection_use_content = '';
        $sparql_endpoint_config = $sparql_endpoint_connection->getConfig();
        if ($sparql_endpoint_config['pp_server_id'] == 0) {
          $title = '<div class="semantic-connector-led" data-server-id="' . $sparql_endpoint_connection->id() . '" data-server-type="sparql-endpoint" title="' . t('Checking service') . '"></div>';
          $title .= Link::fromTextAndUrl($sparql_endpoint_connection->getTitle(), Url::fromUri($sparql_endpoint_connection->getUrl(), array('attributes' => array('target' => array('_blank')))))->toString();

          if (in_array('smart_glossary', $installed_semantic_modules)) {
            $smart_glossary_project_uses = array();
            if (isset($connections_used[$sparql_endpoint_connection->id()]) && isset($connections_used[$sparql_endpoint_connection->id()]['smart_glossary'])) {
              foreach ($connections_used[$sparql_endpoint_connection->id()]['smart_glossary'] as $smart_glossary_use) {
                $smart_glossary_project_uses[] = '<li>' . Link::fromTextAndUrl($smart_glossary_use['title'], Url::fromRoute('entity.smart_glossary.edit_form', array('smart_glossary' => $smart_glossary_use['id'])))->toString() . '</li>';
              }
            }
            if (!empty($smart_glossary_project_uses)) {
              $sparql_connection_use_content .= '<ul>' . implode('', $smart_glossary_project_uses) . '</ul>';
            }
            $sparql_connection_use_content .= '<div class="add-configuration">' . Link::fromTextAndUrl(t('Add new configuration'), Url::fromRoute('entity.smart_glossary.fixed_connection_add_form', array('connection' => $sparql_endpoint_connection->id()), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector'))))->toString() . '</div>';

            $sparql_endpoint_rows[] = array(
              new FormattableMarkup($title, array()),
              new FormattableMarkup($sparql_connection_use_content, array()),
              new FormattableMarkup(SemanticConnector::themeConnectionButtons($sparql_endpoint_connection, empty($uses)), array()),
            );
          }
          else {
            $sparql_endpoint_header = array(t('URL'), t('Operations'));
            $sparql_endpoint_rows[] = array(
              new FormattableMarkup($title, array()),
              new FormattableMarkup(SemanticConnector::themeConnectionButtons($sparql_endpoint_connection, empty($uses)), array()),
            );
          }
        }
      }

      $output['sparql_endpoints'] = array(
        '#theme' => 'table',
        '#header' => $sparql_endpoint_header,
        '#rows' => $sparql_endpoint_rows,
        '#empty' => t('There are no custom SPARQL endpoint connections'),
        '#attributes' => array(
          'id' => 'sparql-endpoints-table',
          'class' => array('semantic-connector-tablesorter'),
        ),
      );
    }

    // Add CSS and JS.
    $output['#attached'] = array(
      'library' =>  array(
        'semantic_connector/admin_area',
        'semantic_connector/tablesorter',
      ),
    );

    return $output;
  }

  /**
   * Refresh (resave) a SemanticConnectorConnection.
   *
   * @param SemanticConnectorConnection $connection
   *   The connection to refresh.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returning the user to the connection overview.
   */
  public function refreshConnection($connection) {
    $connection->save();
    \Drupal::messenger()->addMessage(t('Successfully refreshed connection "%contitle".', array('%contitle' => $connection->getTitle())));

    return new RedirectResponse(Url::fromRoute('semantic_connector.overview')->toString());
  }

  /**
   * Check if a SemanticConnectorConnection is available.
   *
   * @param SemanticConnectorConnection $connection
   *   The connection to check for availability.
   * @param string $return_type
   *   How to return the value ("ajax" or "boolean")
   *
   * @return bool
   *   TRUE if the connection is available, FALSE if not
   */
  public function connectionAvailable($connection, $return_type = 'boolean') {
    $available = $connection->available();

    if ($return_type == 'ajax') {
      echo $available ? 1 : 0;
      exit();
    }
    else {
      return $available;
    }
  }

  /**
   * A callback to refresh the notifications; the logic is done by
   * the SemanticConnectorNotificationsSubscriber class.
   */
  public function refreshNotifications() {
    // Clear the messages and return to the page where the user came from.
    \Drupal::messenger()->deleteAll();
    \Drupal::messenger()->addMessage('Successfully refreshed the global notifications.');

    // Drupal Goto to forward a destination if one is available.
    $url = Url::fromUri('internal:/');
    if (\Drupal::request()->query->has('destination')) {
      $destination = \Drupal::request()->get('destination');
      if (\Drupal::service('path.current')->getPath() != $destination) {
        $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . $destination);
      }
    }

    return new RedirectResponse($url->toString());
  }
}
