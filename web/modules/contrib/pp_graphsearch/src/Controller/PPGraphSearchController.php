<?php

/**
 * @file
 * Contains \Drupal\pp_graphsearch\Controller\PPGraphSearchController class.
 */

namespace Drupal\pp_graphsearch\Controller;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\pp_graphsearch\Entity\PPGraphSearchConfig;
use Drupal\pp_graphsearch\PPGraphSearch;
use Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection;
use Drupal\semantic_connector\SemanticConnector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for the PoolParty GraphSearch module.
 */
class PPGraphSearchController extends ControllerBase implements ContainerInjectionInterface {
  protected $databaseConnection;

  public static function create(ContainerInterface $container) {
    /** @var Connection $db_connection */
    $db_connection = $container->get('database');
    return new static($db_connection);
  }

  public function __construct(Connection $databaseConnection) {
    $this->databaseConnection = $databaseConnection;
  }

  /**
   * Returns markup for our custom page.
   */
  /**
   * Get the results of the PoolParty GraphSearch via AJAX.
   *
   * @param PPGraphSearchConfig $graphsearch_config
   *   The PoolParty GraphSearch configuration
   * @param int $page
   *   The page to start searching at
   *
   * @return string
   *   The rendered filters and results in a json format
   */
  public function getResults($graphsearch_config, $page) {
    $filters = isset($_GET['filters']) ? $_GET['filters'] : array();
    $graphsearch = new PPGraphSearch($graphsearch_config);
    $graphsearch->setFilters($filters);
    $graphsearch->search($page);

    $content_array = $graphsearch->themeContent();
    // Render every sub-area of the content array.
    $content_list = array();
    foreach ($content_array as $content_key => $content_area) {
      if (substr($content_key, 0, 1) != '#') {
        $content_list[$content_key] = \Drupal::service('renderer')->render($content_area);
      }
    }

    $filters = $graphsearch->themeFilters();
    $data = array(
      'list' => $content_list,
      'filter' => \Drupal::service('renderer')->render($filters),
    );

    print \Drupal\Component\Serialization\Json::encode($data);
    exit();
  }

  /**
   * Get the RSS content by a given PoolParty GraphSearch configuration set ID.
   *
   * @param PPGraphSearchConfig $graphsearch_config
   *   The PoolParty GraphSearch configuration
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The RSS content
   */
  public function getRss($graphsearch_config) {
    // Check if the configuration set exists
    $graphsearch = new PPGraphSearch($graphsearch_config);

    // There are filters, so look for an existing short-URL.
    if (isset($_GET['filters'])) {
      $filter_string = UrlHelper::buildQuery(array('filters' => $_GET['filters']));

      // Check if there is a short URL available for this set of filters and
      // this configuration set id.
      $shorturl_query = $this->databaseConnection->select('pp_graphsearch_rss_shorturls', 'su');
      $shorturl_query->fields('su', array('short_url'));
      $shorturl_query->condition('pp_graphsearch_id', $graphsearch_config->id());
      $shorturl_query->condition('filter_string', $filter_string);
      $shorturl = $shorturl_query->execute()->fetchField();

      if ($shorturl === FALSE) {
        // Create a new short URL for the filter-combination.
        $rsssuid = $this->databaseConnection->insert('pp_graphsearch_rss_shorturls')->fields(
          array(
            'pp_graphsearch_id' => $graphsearch_config->id(),
            'filter_string' => $filter_string,
          )
        )->execute();

        $shorturl = $this->createShorturl($rsssuid);

        // Update the database with the created shorturl.
        $shorturl_query = $this->databaseConnection->update('pp_graphsearch_rss_shorturls');
        $shorturl_query->fields(array(
          'short_url' => $shorturl,
        ));
        $shorturl_query->condition('pp_graphsearch_id', $graphsearch_config->id());
        $shorturl_query->condition('filter_string', $filter_string);
        $shorturl_query->execute();
      }

      // Redirect to the short URL.
      $response = new RedirectResponse(Url::fromRoute('pp_graphsearch.get_rss_by_shorturl', array('shorturl' => $shorturl))->toString());
      return $response;
    }
    else {
      $graphsearch->search();
      $content_array = $graphsearch->themeContent('rss');
      $content = $content_array['#markup'];

      return new \Symfony\Component\HttpFoundation\Response($content, 200, array('Content-Type' => 'application/rss+xml; charset=utf-8'));
    }
  }

  /**
   * Get the RSS content by a given short URL.
   *
   * @param string $shorturl
   *   The short URL
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The RSS response
   */
  public function getRssByShorturl($shorturl) {
    // Get the configuration set id and the filters from the database.
    $shorturl_info_query = $this->databaseConnection->select('pp_graphsearch_rss_shorturls', 'su');
    $shorturl_info_query->fields('su', array('pp_graphsearch_id', 'filter_string'));
    $shorturl_info_query->condition('short_url', $shorturl);
    $shorturl_infos = $shorturl_info_query->execute()->fetch();
    $content = '';

    // If the shorturl exists, set the filters and display the content.
    if ($shorturl_infos !== FALSE) {
      $graphsearch = new PPGraphSearch(PPGraphSearchConfig::load($shorturl_infos->pp_graphsearch_id));
      parse_str(parse_url('?' . $shorturl_infos->filter_string, PHP_URL_QUERY), $filter_array);
      $graphsearch->setFilters($filter_array['filters']);
      $graphsearch->search();
      $content_array = $graphsearch->themeContent('rss');
      $content = $content_array['#markup'];
    }

    return new \Symfony\Component\HttpFoundation\Response($content, 200, array('Content-Type' => 'application/rss+xml; charset=utf-8'));
  }

  /**
   * Get similar documents from a given document via AJAX.
   *
   * @param PPGraphSearchConfig $graphsearch_config
   *   The PoolParty GraphSearch configuration
   *
   * @return string
   *   The rendered content
   */
  public function getSimilars($graphsearch_config) {
    if (isset($_GET['uri'])) {
      $graphsearch = new PPGraphSearch($graphsearch_config);
      $graphsearch->searchSimilars($_GET['uri']);
      print \Drupal::service('renderer')->render($graphsearch->themeSimilars());
    }
    exit();
  }

  /**
   * Get the suggested concepts for the autocomplete field in the search bar.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param PPGraphSearchConfig $graphsearch_config
   *   The PoolParty GraphSearch configuration.
   * @param int $max_items
   *   The maximum number of concepts displayed in the drop down list.
   * @param string $facet_id
   *   The ID of the facet to filter the suggestions for.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, $graphsearch_config, $max_items, $facet_id = "") {
    $graphsearch_api = $graphsearch_config->getConnection()->getApi('sonr');
    $matches = array();
    $string = $request->query->get('q');
    if ($string) {
      // We need the search information to get the correct language.
      $connection_config = $graphsearch_config->getConnection()->getConfig();
      $connection_graphsearch_config = $connection_config['graphsearch_configuration'];
      $searchspaces = SemanticConnector::getGraphSearchSearchSpaces($connection_graphsearch_config, $graphsearch_config->getSearchSpaceId());

      $parameters = array(
        'count' => $max_items,
        'locale' => $searchspaces[$graphsearch_config->getSearchSpaceId()]['language'],
      );
      if (!empty($facet_id)) {
        $parameters['context'] = $facet_id;
      }

      if (($result = $graphsearch_api->suggest($string, $graphsearch_config->getSearchSpaceId(), $parameters)) !== FALSE) {
        $config_settings = $graphsearch_config->getConfig();
        $facet_labels =  [];
        // Get the facet labels.
        if ($config_settings['ac_add_context'] && $config_settings['ac_add_facet_name']) {
          $pp_graphearch = new PPGraphSearch($graphsearch_config);
          $facet_labels = $pp_graphearch->getAllFacets();

          // Overwrite the facet labels with custom ones if available.
          foreach ($config_settings['facets_to_show'] as $facet) {
            if (!empty($facet['name'])) {
              $facet_labels[$facet['facet_id']] = $facet['name'];
            }
          }
        }

        foreach ($result['results'] as $data) {
          $matches[] = array(
            'value' => $data['field'] . '|' . $data['id'],
            'label' => $data['label'],
            'matching_label' => $data['matchingLabel'],
            'context' => $data['context'] . ($config_settings['ac_add_facet_name'] && isset($facet_labels[$data['field']]) ? ' (' . $facet_labels[$data['field']] . ')' : ''),
          );
        }
      }
    }
    return new JsonResponse($matches);
  }

  /**
   * Saves the selected filters for the logged in user.
   *
   * @param PPGraphSearchConfig $graphsearch_config
   *   The PoolParty GraphSearch configuration
   *
   * @return string
   *   "saved" or an error if not saved.
   */
  public function saveFilter($graphsearch_config) {
    $errors = array();
    if (!isset($_GET['filters']) || empty($_GET['filters'])) {
      $errors[] = t('The filter is empty. Please select your preferred filters.');
    }
    if (!isset($_GET['title']) || empty($_GET['title'])) {
      $errors[] =  t('No title is given. Please add a descriptive title.');
    }
    if (empty($errors)) {
      $filter = UrlHelper::buildQuery(array('filters' => $_GET['filters']));
      $result = $this->databaseConnection->insert('pp_graphsearch_search_filters')
        ->fields(array(
          'uid' => \Drupal::currentUser()->id(),
          'pp_graphsearch_id' => $graphsearch_config->id(),
          'filter_string' => $filter,
          'title' => $_GET['title'],
          'time_interval' => $_GET['time_interval'],
          'timestamp' => time(),
        ))
        ->execute();
      echo $result ? 'saved' : t('Error: Filter can not be saved.');
    }
    else {
      echo implode('<br />', $errors);
    }
    exit();
  }

  /**
   * Returns all the saved filters from the logged in user.
   *
   * @param PPGraphSearchConfig $graphsearch_config
   *   The PoolParty GraphSearch configuration
   *
   * @return string
   *   The saved filters as a HTML list.
   */
  public function getFilters($graphsearch_config) {
    $filter_query = $this->databaseConnection->select('pp_graphsearch_search_filters', 'sf');
    $filter_query->fields('sf', array('sfid', 'title', 'filter_string', 'time_interval', 'timestamp'));
    $filter_query->condition('uid', \Drupal::currentUser()->id());
    $filter_query->condition('pp_graphsearch_id', $graphsearch_config->id());
    $filter_query->orderBy('timestamp');
    $result = $filter_query->execute();

    $settings = $graphsearch_config->getConfig();

    $items = array();
    while ($record = $result->fetchAssoc()) {
      parse_str(parse_url('?' . $record['filter_string'], PHP_URL_QUERY), $filter);
      $filter = \Drupal\Component\Serialization\Json::encode($filter);
      $item ='<a class="search-filter-title" data-filter=\'' . $filter . '\'>' . $record['title'] . '</a>';
      if ($record['time_interval']) {
        $item .= '<div class="search-filter-time-interval">(' . $record['time_interval'] . ' ' . t('e-mail alert') . ')</div>';
      }
      $item .= '<div class="search-filter-timestamp">' . \Drupal::service('date.formatter')->format($record['timestamp'], (isset($settings['date_format']) ? $settings['date_format'] : 'short')) . '</div>';
      $item .= '<a class="search-filter-remove" data-sfid="' . $record['sfid'] . '">' . t('remove') . '</a>';
      $items[] = $item;
    }
    if (!empty($items)) {
      echo '<div class="item-list"><ul><li>' . implode('</li><li>', $items) . '</li></ul></div>';
    }
    else {
      echo t('You haven\' stored any filters yet.');
    }
    exit();
  }

  /**
   * Deletes a saved filter from the logged in user.
   *
   * @param PPGraphSearchConfig $graphsearch_config
   *   The PoolParty GraphSearch configuration
   *
   * @return string
   *   "saved" or an error if not saved.
   */
  public function deleteFilter($graphsearch_config) {
    $result = $this->databaseConnection->delete('pp_graphsearch_search_filters')
      ->condition('sfid', $_GET['sfid'])
      ->condition('uid', \Drupal::currentUser()->id())
      ->condition('pp_graphsearch_id', $graphsearch_config->id())
      ->execute();

    echo $result ? 'deleted' : t("Error: Filter can not be removed");
    exit();
  }

  /**
   * List all saved PoolParty GraphSearch agents.
   *
   * @return array
   *   A renderable array of the list of agents.
   */
  public function listAgents() {
    $output = array();
    $connections = SemanticConnector::getConnectionsByType('pp_server');
    if (!empty($connections)) {
      $intervals = array(
        '3600000' => t('hourly'),
        '86400000' => t('daily'),
        '604800000' => t('weekly'),
      );
      foreach ($connections as $connection) {
        /** @var SemanticConnectorPPServerConnection $connection */
        $connection_config = $connection->getConfig();
        if (!empty($connection_config['graphsearch_configuration'])) {
          $sonr_api = $connection->getApi('sonr');
          $connection_id = $connection->id();

          $graphsearch_config = $connection_config['graphsearch_configuration'];
          if (isset($graphsearch_config['version']) && version_compare($graphsearch_config['version'], '6.1', '>=')) {
            $search_spaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config);
          }
          // Fallback for older versions and errors.
          if (!isset($search_spaces) || empty($search_spaces)) {
            $search_spaces = ['' => ['name' => '']];
          }

          foreach ($search_spaces as $search_space_id => $search_space) {
            $link = Link::fromTextAndUrl($connection->getTitle(), Url::fromRoute('semantic_connector.overview', array(), array('fragment' => 'pp-server-' . $connection_id)))
              ->toString();
            $title = '<h3 class="semantic-connector-table-title">' . t('Agents for PoolParty GraphSearch on PoolParty server "%link"', array('%link' => $link)) . (!empty($search_space_id) ? ' ' . t('for agent %agentname', array('%agentname' => $search_space['name'])) : '') . '</h3>';
            $output['agents_title_' . $connection_id . '_' . $search_space_id] = array(
              '#markup' => $title,
            );

            $rows = array();
            $indexed_agents = array();
            if (($agents = $sonr_api->getAgents($search_space_id)) !== FALSE) {
              $indexed_agents = $sonr_api->getIndexedAgents($search_space_id);
              foreach ($agents as $agent) {
                $agent_id_full = implode('|', array($connection_id, $search_space_id, urlencode($agent->id)));
                $actions = array(
                  Link::fromTextAndUrl(t('Edit'), Url::fromRoute('pp_graphsearch.edit_agent', array(), array('query' => array('agent_id_full' => $agent_id_full))))->toString(),
                  Link::fromTextAndUrl(t('Delete'), Url::fromRoute('pp_graphsearch.delete_agent', array(), array('query' => array('agent_id_full' => $agent_id_full))))->toString(),
                  Link::fromTextAndUrl(t('Start crawling'), Url::fromRoute('pp_graphsearch.run_agent', array(), array('query' => array('agent_id_full' => $agent_id_full))))->toString(),
                );
                $rows[] = array(
                  $agent->configuration['source'],
                  Link::fromTextAndUrl($agent->configuration['url'], Url::fromUri($agent->configuration['url'], array('attributes' => array('target' => '_blank'))))
                    ->toString(),
                  $intervals[$agent->configuration['periodMillis']],
                  new FormattableMarkup($this->showAgentPercentageBar($agent), array()),
                  new FormattableMarkup(implode(' | ', $actions), array()),
                );
                if (isset($indexed_agents[$agent->configuration['source']])) {
                  unset($indexed_agents[$agent->configuration['source']]);
                }
              }
            }
            $output['agents_' . $connection_id . '_' . $search_space_id] = array(
              '#theme' => 'table',
              '#header' => array(
                t('Agent name'),
                t('URL'),
                t('Crawling interval'),
                t('Next crawling'),
                t('Operations'),
              ),
              '#rows' => $rows,
            );

            if (!empty($indexed_agents)) {
              $table = array();
              $rows = array();
              foreach ($indexed_agents as $agent) {
                $agent_id_full = implode('|', array($connection_id, $search_space_id, urlencode($agent)));
                $actions = array(
                  Link::fromTextAndUrl(t('Delete indexed feed items'), Url::fromRoute('pp_graphsearch.delete_agent_index', array(), array('query' => array('agent_id_full' => $agent_id_full))))->toString(),
                );
                $rows[] = array(
                  $agent,
                  new FormattableMarkup(implode(' | ', $actions), array()),
                );
              }
              $table['deleted_agents'] = array(
                '#theme' => 'table',
                '#header' => array(
                  t('Source name'),
                  t('Operations'),
                ),
                '#rows' => $rows,
              );
              $output['deleted_agents_fieldset_' . $connection_id . '_' . $search_space_id] = array(
                '#theme' => 'details',
                '#title' => t('Agents that have been deleted, but their feed items are still stored in the search index'),
                '#open' => FALSE,
                '#children' => \Drupal::service('renderer')->render($table),
                '#attributes' => array(
                  'class' => array(
                    'collapsible',
                    'collapsed',
                    'deleted_agents'
                  ),
                ),
              );
            }
          }
        }
      }
    }

    // Add CSS and JS.
    $output['#attached'] = array(
      'library' =>  array(
        'pp_graphsearch/admin_area',
      ),
    );

    return $output;
  }

  /**
   * List all saved PoolParty servers.
   *
   * @return array
   *   The renderable HTML of the list of PoolParty servers.
   */
  public function syncronizationOverview() {
    $output = array();

    $output['pp_graphsearch_sync_title'] = array(
      '#markup' => '<h3 class="semantic-connector-table-title">' . t('Synchronization of entities with a PoolParty GraphSearch server') . '</h3>',
    );

    // Get all the content types related to a PoolParty GraphSearch server.
    $variables = \Drupal::config('pp_graphsearch.settings')->get('content_type_push');
    $content_types = array();
    if (!empty($variables)) {
      foreach ($variables as $content_type => $settings) {
        if (empty($content_type) || !$settings['active']) {
          continue;
        }
        if (!isset($content_types[$settings['connection_id']])) {
          $content_types[$settings['connection_id']] = array();
        }
        $node_type = $type = NodeType::load($content_type);
        if ($content_type == 'user') {
          $content_types[$settings['connection_id']][$settings['search_space_id']][] = 'User';
        }
        else {
          $content_types[$settings['connection_id']][$settings['search_space_id']][] = $node_type->label();
        }
      }
    }

    $rows = array();
    $pp_servers = SemanticConnector::getConnectionsByType('pp_server');
    /** @var SemanticConnectorPPServerConnection $pp_server */
    foreach ($pp_servers as $pp_server) {
      $pp_config = $pp_server->getConfig();
      if (!empty($pp_config['graphsearch_configuration'])) {
        $title = '<div class="semantic-connector-led" data-server-id="' . $pp_server->id() . '" data-server-type="pp-server" title="' . t('Checking service') . '"></div>';
        $title .= Link::fromTextAndUrl($pp_server->getTitle(), Url::fromUri($pp_server->getUrl() . '/' . $pp_server->getGraphSearchPath(), array('attributes' => array('target' => '_blank'))))->toString();

        // Get the search space label.
        $search_space_labels = array();
        $search_spaces = SemanticConnector::getGraphSearchSearchSpaces($pp_config['graphsearch_configuration']);
        if (version_compare($pp_config['graphsearch_configuration']['version'], '6.1', '>=')) {
          foreach ($search_spaces as $search_space) {
            $search_space_labels[$search_space['id']] = (!empty($search_space['name']) ? $search_space['name'] : t('no search space found'));
          }
        }
        else {
          foreach ($pp_config['projects'] as $project) {
            if (isset($pp_config['graphsearch_configuration']['projects'][$project['id']]) && isset($search_spaces[$project['id']])) {
              $search_space_labels[$project['id']] = $project['title'];
              break;
            }
          }
        }

        foreach ($search_space_labels as $search_space_id => $search_space_label) {
          $nodes = (!isset($content_types[$pp_server->id()]) || !isset($content_types[$pp_server->id()][$search_space_id])) ? '<div class="semantic-connector-italic">' . t('not yet set') . '</div>' : '<div class="item-list"><ul><li>' . implode('</li><li>', $content_types[$pp_server->getId()][$search_space_id]) . '</li></ul></div>';

          $actions = array();
          if (isset($content_types[$pp_server->id()]) && isset($content_types[$pp_server->id()][$search_space_id])) {
            $actions = array(
              Link::fromTextAndUrl(t('Synchronize entities'), Url::fromRoute('pp_graphsearch.sync_update', array('connection_id' => $pp_server->id(), 'search_space_id' => $search_space_id)))
                ->toString(),
              Link::fromTextAndUrl(t('Remove entities'), Url::fromRoute('pp_graphsearch.delete_sync', array('connection_id' => $pp_server->id(), 'search_space_id' => $search_space_id)))
                ->toString(),
            );
          }

          $rows[] = array(
            new FormattableMarkup($title, array()),
            $search_space_label,
            new FormattableMarkup($nodes, array()),
            new FormattableMarkup(implode(' | ', $actions), array()),
          );
        }
      }
    }

    $output['sonr_webmining_sync'] = array(
      '#theme' => 'table',
      '#header' => array(
        t('PoolParty GraphSearch server'),
        t('Selected search space'),
        t('Available in content type'),
        t('Operations'),
      ),
      '#rows' => $rows,
      '#attributes' => array(
        'id' => 'sonr-webmining-sync-table',
        'class' => array('semantic-connector-tablesorter'),
      ),
      '#attached' => array(
        'library' =>  array(
          'pp_graphsearch/admin_area',
          'semantic_connector/tablesorter',
        ),
      ),
    );

    return $output;
  }

  /******************************************************
   * Helper functions
   *****************************************************/

  /**
   * Create a shorturl out of an integer.
   *
   * @param int $rsssuid
   *   The ID of the RSS short URL
   *
   * @return string
   *   The short URL
   */
  private function createShorturl($rsssuid) {
    $rsssuid--;

    $chars_to_use = range('a', 'z');
    $chars_to_fill = range('A', 'Z');
    $length = count($chars_to_use);
    $code = '';

    // Calculate the new shorturl.
    while ($rsssuid > $length - 1) {
      // Determine the value of the next higher character.
      $code = $chars_to_use[(int) fmod((float) $rsssuid, (float) $length)] . $code;
      // Reset $rsssuid to remaining value to be converted.
      $rsssuid = floor($rsssuid / $length) - 1;
    }

    // Remaining value of $id is less than the length of $chars_to_use.
    $code = $chars_to_use[$rsssuid] . $code;

    // Add fill-characters to always have a string of 6 chars length.
    $fill_string = "";
    $max = count($chars_to_fill) - 1;
    for ($i = 0; $i < (6 - strlen($code)); $i++) {
      $rand = mt_rand(0, $max);
      $fill_string .= $chars_to_fill[$rand];
    }

    return $code . $fill_string;
  }

  /**
   * Shows a bar in percentage when the next run starts.
   * TODO: If an Agent has problems then send an email (adapt PoolParty GraphSearch server configuration)
   *
   * @param object $agent
   *   An agent
   *
   * @return string
   *   The bar
   */
  private function showAgentPercentageBar($agent) {
    if ($agent->status['running']) {
      return '<div class="percentage-bar running"><span class="bar" style="width: 100%"></span>
      <span class="tooltip">
        <strong>' . t('Agent is crawled') . '</strong>
      </span></div>
    ';
    }

    $period = $agent->configuration['periodMillis'] / 1000;
    $time_to_next_run = $agent->status['nextRun'] / 1000 - time();
    $time_to_last_run = time() - $agent->status['lastRun'] / 1000;

    if ($time_to_next_run < 0) {
      $last_run = $this->timeToText($time_to_last_run);
      return '<div class="percentage-bar error"><span class="bar" style="width: 100%"></span>
      <span class="tooltip">
        <strong>' . t('Last crawling') . ':</strong><br />' . $last_run . ' ago<br />
        <strong>' . t('Agent problem') . ':</strong><br />' . t('Please start agent manually!') . '
      </span></div>
    ';
    }

    $percentage = round(100 * $time_to_last_run / $period);

    $next_run = $this->timeToText($time_to_next_run, 'ceil');
    $last_run = $this->timeToText($time_to_last_run, 'floor');

    return '<div class="percentage-bar"><span class="bar" style="width: ' . $percentage . '%"></span>
    <span class="tooltip">
      <strong>' . t('Next crawling') . ':</strong><br /> ' . t('in @time', array('@time' => $next_run)) . '<br />
      <strong>' . t('Last crawling') . ':</strong><br /> ' . t('@time ago', array('@time' => $last_run)) . '
    </span></div>
  ';
  }

  /**
   * Converts a timestamp into human readable format
   *
   * @param int $time
   *   A timestamp
   * @param string $round
   *   Type of rounding (round|ceil|floor)
   *
   * @return string
   *   A human readable time
   */
  private function timeToText($time, $round='round') {
    $time = $time / 60;
    $minutes = (int) $time;
    if ($minutes < 60) {
      return t('%minutes min', array('%minutes' => $minutes));
    }

    $time = $time / 60;
    $hours = (int) $time;
    $minutes = $round(($time - $hours) * 60);
    if ($minutes >= 60) {
      $hours++;
      $minutes = 0;
    }
    if ($hours < 24) {
      return t('%hours hr %minutes min', array('%hours' => $hours, '%minutes' => $minutes));
    }

    $time = $time / 24;
    $days = (int) $time;
    $hours = $round(($time - $days) * 24);
    if ($hours >= 24) {
      $days++;
      $hours = 0;
    }
    return t('%days days %hours hours', array('%days' => $days, '%hours' => $hours));
  }
}