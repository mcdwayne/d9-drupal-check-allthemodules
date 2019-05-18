<?php

/**
 * @file
 * The main class of the PoolParty GraphSearch module.
 */

namespace Drupal\pp_graphsearch;
use Drupal\block\Entity\Block;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging\PowerTagging;
use Drupal\pp_graphsearch\Entity\PPGraphSearchConfig;
use Drupal\semantic_connector\Api\SemanticConnectorSonrApi;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * A collection of static functions offered by the PoolParty GraphSearch module.
 */
class PPGraphSearch {
  protected $filters;
  /** @var SemanticConnectorSonrApi $graphSearchApi */
  protected $graphSearchApi;
  protected $result;
  protected $config;
  protected $config_settings;

  /**
   * Constructor of the SonrWebmining-class.
   *
   * @param $config PPGraphSearchConfig
   *   The configuration of the PoolParty GraphSearch.
   */
  public function __construct($config) {
    $this->config = $config;
    $this->config_settings = $config->getConfig();
    $this->graphSearchApi = $config->getConnection()->getApi('sonr');
    $this->filters = array();
    $this->result = NULL;
  }

  /**
   * This method checks if the PP GraphSearch server exists and is running.
   *
   * @return bool
   *   TRUE if the service is available, FALSE if not
   */
  public function availableApi() {
    return $this->graphSearchApi->available();
  }

  /**
   * Setter-function for the filters-variable.
   *
   * @param array $filters
   *   Array of filters
   */
  public function setFilters($filters) {
    if (is_array($filters) && !empty($filters) && !is_null($filters)) {
      foreach ($filters as $key => $filter) {
        if (is_array($filter)) {
          $filter = (object) $filter;
        }
        $filters[$key] = $filter;
      }
      $this->filters = $filters;
    }
  }

  /**
   * Getter-function for the filters-variable.
   *
   * @return array
   *   Array of filters
   */
  public function getFilters() {
    return $this->filters;
  }

  /**
   * Getter-function for the result-variable.
   *
   * @return array
   *   Array of results
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Getter-function for the config-variable.
   *
   * @return PPGraphSearchConfig
   *   Config of the configuration set
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Returns the URL parameters (uri and search) as a list of filter objects.
   *
   * @return array
   *   A list of filter objects.
   */
  public function getFiltersFromUrlParameter() {
    $filters = array();
    // Get all uri parameters if it is set.
    if (isset($_GET['uri'])) {
      if (!is_array($_GET['uri'])) {
        $_GET['uri'] = array($_GET['uri']);
      }
      // Get all aggregated facets.
      $facet_modes = array();
      foreach ($this->config_settings['facets_to_show'] as $facet) {
        if ($facet['selected']) {
          $facet_modes[$facet['facet_id']] = $facet['facet_mode'];
        }
      }
      // Create the mapping between conceptScheme URI and facet name.
      $facet_map = array();
      if ($field_config = $this->graphSearchApi->getFieldConfig($this->config->getSearchSpaceId())) {
        foreach ($field_config['searchFields'] as $config) {
          if ($config['facet'] && isset($config['conceptScheme'])) {
            $facet_map[$config['conceptScheme']] = $config['name'];
          }
        }
      }

      foreach ($_GET['uri'] as $uri) {
        if (!UrlHelper::isValid($uri)) {
          continue;
        }
        // Get the concept.
        $concept = $this->getConcept($uri, array('skos:prefLabel', 'skos:ConceptScheme', 'skos:broader'));
        if (!empty($concept)) {
          $field = SemanticConnectorSonrApi::ATTR_ALL_CONCEPTS;
          $concept_schemes = $this->sortConceptUris($concept['conceptSchemes'], $facet_modes, $facet_map);
          foreach ($concept_schemes as $concept_scheme) {
            $facet = isset($facet_map[$concept_scheme]) ? $facet_map[$concept_scheme] : '';
            // Check if the facet is shown, the facet mode is aggregated, the
            // concept is a topConcept and the facet is aggregated
            // then show the result with aggregated concepts.
              if ($facet && isset($facet_modes[$facet]) && $facet_modes[$facet] == 'aggregated' && !isset($concept['broaders'])) {
              $field = $facet;
              break;
            }
            // Check if the facet is shown, the facet mode is tree and the facet
            // is aggregated then show the result with aggregated concepts.
            elseif ($facet && isset($facet_modes[$facet]) && ($facet_modes[$facet] == 'tree' || $facet_modes[$facet] == 'list')) {
              $field = $facet;
              break;
            }
          }

          $filters[] = (object) array(
            'field' => $field,
            'value' => $uri,
            'label' => $concept['prefLabel'],
          );
        }
      }
    }

    // Get all search parameters if it is set and allowed.
    if (isset($_GET['search']) && strpos($this->config_settings['search_type'], 'free-term') !== FALSE) {
      $phrases = is_array($_GET['search']) ? $_GET['search'] : explode(' ', $_GET['search']);
      foreach ($phrases as $phrase) {
        $filters[] = (object) array(
          'field' => SemanticConnectorSonrApi::ATTR_CONTENT,
          'value' => strip_tags(trim($phrase)),
        );
      }
    }

    return array_unique($filters, SORT_REGULAR);
  }

  /**
   * Search for aggregated content.
   *
   * @param int $page
   *   (optional) The page to start searching at
   *
   * @return bool
   *   TRUE if the search was successful, FALSE if not
   */
  public function search($page = 0) {
    $facets = array();
    if (!empty($this->config_settings['facets_to_show'])) {
      foreach ($this->config_settings['facets_to_show'] as $facet) {
        if ($facet['selected']) {
          $facets[$facet['facet_id']] = $facet;
        }
      }
    }

    // We need the search information to get the correct language.
    $connection_config = $this->config->getConnection()->getConfig();
    $graphsearch_config = $connection_config['graphsearch_configuration'];
    $searchspaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config, $this->config->getSearchSpaceId());

    $parameters = array(
      'count' => $this->config_settings['items_per_page'],
      'start' => $page * $this->config_settings['items_per_page'],
      'maxFacetCount' => $this->config_settings['facet_max_items'],
      'locale' => $searchspaces[$this->config->getSearchSpaceId()]['language'],
    );

    // Add additional data for every result item
    if ($this->config_settings['show_sentiment']) {
      $this->graphSearchApi->addCustomAttribute(SemanticConnectorSonrApi::ATTR_SENTIMENT);
    }
    if ($this->config_settings['show_tags']) {
      $this->graphSearchApi->addCustomAttribute(SemanticConnectorSonrApi::ATTR_ALL_CONCEPTS);
    }
    $this->graphSearchApi->addCustomAttribute(SemanticConnectorSonrApi::ATTR_CONTENT_TYPE);
    $variables = \Drupal::config('pp_graphsearch.settings')->get('content_type_push');

    $taxonomies = self::getAllConnectedTaxonomies($variables, 'machine_name');
    foreach ($taxonomies as $taxonomy) {
      $this->graphSearchApi->addCustomAttribute('dyn_lit_' . $taxonomy);
    }

    $custom_attributes = $this->graphSearchApi->getCustomAttributes();
    \Drupal::moduleHandler()->alter('pp_graphsearch_custom_attributes', $custom_attributes, $this);
    $this->graphSearchApi->setCustomAttributes($custom_attributes);

    // Call the search method.
    $this->result = $this->graphSearchApi->search($this->config->getSearchSpaceId(), $facets, $this->filters, $parameters);

    // Prepare the facets.
    if (is_array($this->result) && !empty($this->result['facetList'])) {
      foreach ($this->result['facetList'] as $key => $facet) {
        $facet['facets'] = array_slice($facet['facets'], 0, $facets[$facet['field']]['max_items']);
        if ($facets[$facet['field']]['facet_mode'] == 'tree') {
          $facet['facets'] = $this->clipTree($facet['facets'], $facets[$facet['field']]['max_items'], $facets[$facet['field']]['tree_depth']);
        }
        if (!empty($facets[$facet['field']]['name'])) {
          $facet['label'] = $facets[$facet['field']]['name'];
        }
        $this->result['facetList'][$facet['field']] = $facet;
        unset($this->result['facetList'][$key]);
      }

      // Sort the facets.
      $this->result['facetList'] = array_replace(array_intersect_key($facets, $this->result['facetList']), $this->result['facetList']);
    }

    return (!is_null($this->result) && $this->result !== FALSE);
  }

  /**
   * Get all available facets of the connected PoolParty GraphSearch-instance.
   *
   * @return array
   *   An associative array of facets: facet_id => facet_label
   */
  public function getAllFacets() {
    return $this->graphSearchApi->getFacets($this->config->getSearchSpaceId());
  }

  /**
   * Search for similar documents.
   *
   * @param string $uri
   *   URI of a document
   *
   * @return bool
   *   TRUE if the search was successful, FALSE if not
   */
  public function searchSimilars($uri) {
    // We need the search information to get the correct language.
    $connection_config = $this->config->getConnection()->getConfig();
    $graphsearch_config = $connection_config['graphsearch_configuration'];
    $searchspaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config, $this->config->getSearchSpaceId());

    $parameters = array(
      'count' => $this->config_settings['similar_max_items'],
      'locale' => $searchspaces[$this->config->getSearchSpaceId()]['language'],
    );

    // Add additional data for every result item
    if ($this->config_settings['show_sentiment']) {
      $this->graphSearchApi->addCustomAttribute(SemanticConnectorSonrApi::ATTR_SENTIMENT);
    }
    if ($this->config_settings['show_tags']) {
      $this->graphSearchApi->addCustomAttribute(SemanticConnectorSonrApi::ATTR_ALL_CONCEPTS);
    }

    $this->result = $this->graphSearchApi->getSimilar($uri, $this->config->getSearchSpaceId(), $parameters);

    return (!is_null($this->result) && $this->result !== FALSE);
  }

  /**
   * Returns a concept with the URI $uri.
   *
   * @param string $uri
   *   The URI of a concept.
   * @param array $properties
   *   Array of additional concept properties that will be fetched.
   *   If empty all data of the concept will be fetched.
   * @param string $language
   *   The preferred language for the prefLabel.
   *
   * @return array
   *   The concept as an object.
   */
  public function getConcept($uri, array $properties = array(), $language = '') {
    if (!UrlHelper::isValid($uri)) {
      return NULL;
    }
    // Open a new connection to the PPT.
    /** @var \Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection $ppt_connection */
    $ppt_connection = SemanticConnector::getConnection('pp_server');
    $ppt_connection->setUrl($this->config->getConnection()->getUrl());
    $ppt_connection->setCredentials($this->config->getConnection()->getCredentials());
    // Get the API of the PPT.
    /** @var \Drupal\semantic_connector\Api\SemanticConnectorPPTApi $pptApi */
    $pptApi = $ppt_connection->getApi('PPT');
    $connection_config = $this->config->getConnection()->getConfig();
    // Get the concept.
    $properties = empty($properties) ? array('all') : $properties;

    // Disable errors in case the concept is not part of the first project.
    $curl_connection = $pptApi->getConnection();
    $curl_connection->setErrorLogging(FALSE);
    $concept = null;
    foreach (array_keys($connection_config['graphsearch_configuration']['projects']) as $project_id) {
      $concept = $pptApi->getConcept($project_id, $uri, $properties, $language);
      if (!empty($concept)) {
        break;
      }
    }
    $curl_connection->setErrorLogging(TRUE);

    return $concept;
  }

  /**
   * Returns the prefLabel of a concept with the URI $uri.
   *
   * @param string $uri
   *   The URI of a concept.
   * @param string $language
   *   The preferred language for the prefLabel.
   *
   * @return string
   *   The prefLabel of the concept.
   */
  public function getLabelOfConcept($uri, $language = '') {
    $concept = $this->getConcept($uri, array('skos:prefLabel'), $language);
    return empty($concept) ? '' : $concept->prefLabel;
  }

  /**
   * Show aggregated content.
   *
   * @param string $display_type
   *   The way of displaying the data, can be "html" or "rss"
   * @param array $display_parameters
   *   Array of parameters depending on the chosen display type:
   *     for html:
   *       - bool add_filter_form:
   *          TRUE if a filter form shell be added,
   *          FALSE if not
   *     for rss:
   *       no parameters yet
   *
   * @return array
   *   The renderable content
   */
  public function themeContent($display_type = 'html', $display_parameters = array()) {
    // Search was not yet started, or didn't succeed.
    if (is_null($this->result)) {
      \Drupal::messenger()->addMessage(t('There was an error trying to get the search results from PoolParty GraphSearch.'), 'error');
    }
    else if ($this->result === FALSE) {
      \Drupal::messenger()->addMessage(t('The PoolParty GraphSearch server is currently not available. Please try again later or contact the server administrator.'), 'warning');
    }

    switch ($display_type) {
      case 'html':
        return $this->themeAsView($display_parameters);

      case 'rss':
        return $this->themeAsRSSfeed($display_parameters);

      default:
        return array();
    }
  }

  /**
   * Show the filter-form of the aggregated content.
   *
   * @return array
   *   Renderable array of the content.
   */
  public function themeFilters() {
    $filters_array = array();
    // No filters should be shown or the search was not yet started
    // (or didn't succeed).
    if (is_null($this->result)) {
      $filters_array['#markup'] = '';
    }
    else {
      $filters_array['#attached']['drupalSettings']['pp_graphsearch']['hide_empty_facet'] = $this->config_settings['hide_empty_facet'];
      /** @noinspection PhpMethodParametersCountMismatchInspection */
      $filters_array['form'] = \Drupal::formBuilder()->getForm('\Drupal\pp_graphsearch\Form\PPGraphSearchFiltersForm', $this);
    }

    return $filters_array;
  }

  /**
   * Show the search bar with the textfield and buttons.
   *
   * @return array
   *   The renderable array of the form.
   */
  protected function themeSearchBar() {
    $array_to_render = array();

    if (!isset($this->config_settings['show_searchbar']) || !$this->config_settings['show_searchbar']) {
      $array_to_render['#attached']['drupalSettings']['pp_graphsearch'] = array(
        'search_bar' => FALSE,
        'search_type' => $this->config_settings['search_type'],
      );
    }
    else {
      $array_to_render['#attached']['drupalSettings']['pp_graphsearch'] = array(
        'search_bar' => TRUE,
        'min_chars' => $this->config_settings['ac_min_chars'],
        'add_matching_label' => $this->config_settings['ac_add_matching_label'],
        'add_context' => $this->config_settings['ac_add_context'],
        'search_type' => $this->config_settings['search_type'],
      );

      /** @noinspection PhpMethodParametersCountMismatchInspection */
      $array_to_render['form'] = \Drupal::formBuilder()->getForm('\Drupal\pp_graphsearch\Form\PPGraphSearchSearchBarForm', $this);
    }

    return $array_to_render;
  }

  /**
   * Show the facet box for the selected concepts and free terms.
   *
   * @return array
   *   The renderable array of the form.
   */
  protected function themeFacetBox() {
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $array_to_render = \Drupal::formBuilder()->getForm('\Drupal\pp_graphsearch\Form\PPGraphSearchFacetBoxForm', $this);
    return $array_to_render;
  }

  /**
   * Show the "more like this" link for similar documents.
   *
   * @return array
   *   Renderable array of the content.
   */
  public function themeSimilars() {
    $display_parameters = array(
      'only_documents' => TRUE,
    );
    return $this->themeAsView($display_parameters);
  }

  /**
   * Theme PoolParty GraphSearch results as a view.
   *
   * @param array $variables
   *   Array of variables
   *
   * @return array
   *   The renderable content
   */
  protected function themeAsView($variables = array()) {
    global $base_url;
    $build_array = array();

    // Add search bar.
    if (!isset($variables['only_documents']) || !$variables['only_documents']) {
      $build_array['search_bar'] = $this->themeSearchBar();

      // Add the facet box for the selected concepts (facets).
      if ($this->config_settings['show_facetbox']) {
        $build_array['facet_box'] = $this->themeFacetBox();
      }

      // Add an RSS-button in the header of the view if required.
      if ($this->config_settings['add_rss_functionality']) {
        $rss_link = $base_url . '/pp-graphsearch/get-rss/' . $this->config->id() . ((!empty($this->filters)) ? '?' . http_build_query(array('filters' => $this->filters)) : '');
        $build_array['rss_button'] = array(
          '#markup' => Link::fromTextAndUrl(t('RSS Feed'), Url::fromUri($rss_link, array('attributes' => array('class' => array('rss-link'), 'target' => '_blank'))))->toString(),
        );
      }

      // Add a total count of results.
      if ($this->config_settings['show_results_count']) {
        $build_array['results_count'] = array(
          '#markup' => '<div class="pp-graphsearch-results-count">' . $this->result['total'] . ' ' . t('results') . '</div>',
        );
      }
    }

    // Add the results.
    $results = array();
    $i = 0;
    if (is_array($this->result) && isset($this->result['results'])) {
      $content_push = \Drupal::config('pp_graphsearch.settings')->get('content_type_push');
      $node_types_by_name = array_flip(node_type_get_names());

      foreach ($this->result['results'] as $item) {
        \Drupal::moduleHandler()->alter('pp_graphsearch_list_item', $item, $this->config, $this->graphSearchApi);

        if (isset($item['customAttributes'][SemanticConnectorSonrApi::ATTR_SOURCE])) {
          $type = $item['customAttributes'][SemanticConnectorSonrApi::ATTR_SOURCE];
          $link = $item['link'];
        }
        elseif (isset($item['customAttributes'][SemanticConnectorSonrApi::ATTR_CONTENT_TYPE])) {
          $type = $item['customAttributes'][SemanticConnectorSonrApi::ATTR_CONTENT_TYPE][0];

          // Check if there is a custom label for that content type.
          $content_type = '';
          if ($type == 'User') {
            $content_type = 'user';
          }
          elseif (in_array($type, array_keys($node_types_by_name))) {
            $content_type = $node_types_by_name[$type];
          }
          if (!empty($content_type) && isset($content_push[$content_type]) && isset($content_push[$content_type]['label'])) {
            $type = $content_push[$content_type]['label'];
          }

          $type .=  ' (' . \Drupal::config('system.site')->get('name') . ')';
          $link = str_replace($base_url . '/', '', $item['link']);
        }
        else {
          $type = \Drupal::config('system.site')->get('name');
          $link = $item['link'];
        }
        $result = array();
        $result['id'] = $item['id'];
        $result['link'] = $link;
        $result['type'] = $type;

        // Newer PoolParty GraphSearch versions always deliver arrays.
        if (is_array($result['type'])) {
          $result['type'] = reset($result['type']);
        }

        $result['created'] = round($item['date'] / 1000);
        $result['title'] = trim($item['title']);
        $result['description'] = $item['description'];

        // Add the dynamic sentiments.
        $result['sentiment'] = NULL;
        $attr_sentiment = SemanticConnectorSonrApi::ATTR_SENTIMENT;
        if ($this->config_settings['show_sentiment'] && isset($item['customAttributes'][$attr_sentiment])) {
          $result['sentiment'] = $item['customAttributes'][$attr_sentiment];
        }

        // Add the tags.
        $result['tags'] = '';
        if ($this->config_settings['show_tags'] && isset($item['customAttributes'][SemanticConnectorSonrApi::ATTR_ALL_CONCEPTS])) {
          if (isset($item['customAttributes'][SemanticConnectorSonrApi::ATTR_ALL_CONCEPTS]['prefLabel'])) {
            $item['customAttributes'][SemanticConnectorSonrApi::ATTR_ALL_CONCEPTS] = array($item['customAttributes'][SemanticConnectorSonrApi::ATTR_ALL_CONCEPTS]);
          }

          // Sort the tags by their prefLabels.
          $sorted_tags = array();
          foreach ($item['customAttributes'][SemanticConnectorSonrApi::ATTR_ALL_CONCEPTS] as $concept) {
            $sorted_tags[$concept['prefLabel']] = $concept;
          }
          uksort($sorted_tags, 'strcasecmp');

          // Choose which tags to show right away and which tags to hide.
          $tags_array = array(
            'show' => array(),
            'hide' => array(),
          );
          foreach ($sorted_tags as $concept) {
            $show_type = (empty($this->config_settings['tags_max_items']) || count($tags_array['show']) < $this->config_settings['tags_max_items']) ? 'show' : 'hide';
            $tags_array[$show_type][] = array(
              'uri' => (isset($concept['uri']) && !empty($concept['uri']) ? $concept['uri'] : ''),
              'html' => $concept['prefLabel'],
            );
          }

          // Get the project ID required for theming the tags.
          $connection_config = $this->config->getConnection()->getConfig();
          $graphsearch_config = $connection_config['graphsearch_configuration'];
          $project_id = $this->config->getSearchSpaceId();
          if (is_array($graphsearch_config)) {
            if (version_compare($graphsearch_config['version'], '6.1', '>=')) {
              $search_spaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config);
              if (isset($search_spaces[$this->config->getSearchSpaceId()])) {
                // A single project is connected with the search space.
                if (count($search_spaces[$this->config->getSearchSpaceId()]['project_ids']) == 1) {
                  $project_id = $search_spaces[$this->config->getSearchSpaceId()]['project_ids'][0];
                }
                // More than one project is connected with the search space
                // --> we can't provide a project ID.
                else {
                  $project_id = '';
                }
              }
            }
          }

          // Theme the tags.
          if (!empty($tags_array['hide'])) {
            $tags = '<span class="tags-show">';
            $tags .= SemanticConnector::themeConcepts($tags_array['show'], $this->config->getConnectionId(), $project_id) . ', <span class="tags-more" title="Show all tags">...</span>';
            $tags .= '<span class="tags-rest hidden">' . SemanticConnector::themeConcepts($tags_array['hide'], $this->config->getConnectionId(), $project_id) . '</span>';
            $tags .= '</span>';
          }
          else {
            $tags = SemanticConnector::themeConcepts($tags_array['show'], $this->config->getConnectionId(), $project_id);
          }
          $result['tags'] = $tags;
        }

        // Add the "more like this"-button.
        $result['more_like_this'] = '';
        if ($this->config_settings['show_similar'] && (!isset($variables['only_documents']) || !$variables['only_documents'])) {
          $result['more_like_this'] = '<div id="similar-docs-' . $this->result['request']['start'] . '-' . $i++ . '" class="similar-docs">
            <a class="similar-more" data-uri="' . $item['id'] . '">' . t('more like this') . '</a>
            <div class="similar-content"></div>
          </div>';
        }

        $results[] = $result;
      }
    }

    // Get possible themes from other module's hooks.
    $themes = \Drupal::moduleHandler()->invokeAll('pp_graphsearch_content_theme');

    if (!empty($themes)) {
      $theme = reset($themes);
    }
    else {
      $theme = 'pp_graphsearch_content';
    }

    $build_array['content'] = array(
      '#theme' => $theme,
      '#results' => $results,
      '#config' => $this->config_settings,
      '#config_id' => $this->config->id(),
      '#view_type' => ((!isset($variables['only_documents']) || !$variables['only_documents']) ? 'default' : 'similar_content'),
    );

    $build_array['#prefix'] = '<div class="pp-graphsearch-area-' . ($this->config_settings['separate_blocks'] ? 'list' : 'right') . '">';
    $build_array['#suffix'] = '</div>';
    $build_array['#attached']['library'][] = 'pp_graphsearch/display' . ($this->config_settings['use_css_file'] ? '' : '_simple');
    $build_array['#attached']['drupalSettings']['pp_graphsearch']['items_per_page'] = $this->config_settings['items_per_page'];

    return $build_array;
  }

  /**
   * Theme PoolParty GraphSearch results as an RSS feed.
   *
   * @param array $variables
   *   Array of variables
   *
   * @return array
   *   The renderable content
   */
  protected function themeAsRSSfeed($variables = array()) {
    global $base_url;

    $items = '';
    if (is_array($this->result) && isset($this->result['results'])) {
      foreach ($this->result['results'] as $item) {
        $args = array(
          'pubDate' => gmdate(DATE_RSS, $item['date'] / 1000),
        );
        \Drupal\Component\Utility\Unicode::truncate($item['description'], 500, TRUE, TRUE);
        $items .= $this->format_rss_item($item['title'], $item['link'], \Drupal\Component\Utility\Unicode::truncate($item['description'], 500, TRUE, TRUE), $args);
      }
    }

    $channel = array();
    $namespaces = array('xmlns:dc' => 'http://purl.org/dc/elements/1.1/');

    $channel_defaults = array(
      'version' => '2.0',
      'title' => t('PoolParty GraphSearch'),
      'link' => $base_url,
      'description' => t('Customized Feed'),
      'language' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      'generator' => 'http://drupal.org/',
    );
    $channel_extras = array_diff_key($channel, $channel_defaults);
    $channel = array_merge($channel_defaults, $channel);

    $output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
    $output .= "<rss version=\"" . $channel["version"] . "\" xml:base=\"" . $base_url . "\"" . new \Drupal\Core\Template\Attribute($namespaces) . ">\n";
    $output .= $this->format_rss_channel($channel['title'], $channel['link'], $channel['description'], $items, $channel['language'], $channel_extras);
    $output .= "</rss>\n";

    return array(
      '#markup' => $output,
    );
  }

  /**
   * Add a chart of trends to an existing Drupal-form.
   *
   * @param array $form
   *   The form, where the filters have to be added.
   * @param FormStateInterface $form_state
   *   The state of the form
   */
  public function addTrendsToForm(&$form, FormStateInterface &$form_state) {
    if (PPGraphSearch::isFlotInstalled() && !empty($this->filters)) {
      $selected_facets = array();
      $unimportant_facet_ids = array(
        SemanticConnectorSonrApi::ATTR_SOURCE,
        SemanticConnectorSonrApi::ATTR_REGIONS,
        SemanticConnectorSonrApi::ATTR_CONTENT,
        SemanticConnectorSonrApi::ATTR_CONTENT_TYPE,
        'type',
        'date-from',
      );
      foreach ($this->filters as $filter) {
        if (!in_array($filter->field, $unimportant_facet_ids)) {
          $selected_facets[] = $filter->value;
        }
      }

      if (!empty($selected_facets)) {
        $config = array(
          'title' => $this->config_settings['trends_title'],
          'description' => $this->config_settings['trends_description'],
          'width' => '100%',
          'height' => '300px',
          'daysToDisplay' => 95,
          'type' => $this->config_settings['trends_chart_type'],
          'colors' => $this->config_settings['trends_colors'],
        );
        $colors = !empty($config['colors']) ? explode(',', $config['colors']) : array();

        $trends_output = array();
        $trends = $this->graphSearchApi->getTrends($selected_facets, $this->config->getSearchSpaceId());
        if (!empty($trends)) {
          $data = array();
          $start_time = (time() - $config['daysToDisplay'] * 60 * 60 * 24) * 1000;
          foreach ($trends as $trend) {
            $uri = $trend['concept']['uri'];
            $data[$uri] = array();
            $data[$uri]['label'] = $trend['concept']['prefLabel'];
            foreach ($trend['trend'] as $value_set) {
              if ($value_set[0] > $start_time) {
                $data[$uri]['trend'][] = array(
                  'time' => (string) $value_set[0],
                  'value' => $value_set[1]
                );
              }
            }
          }
          $data_json = '';
          switch ($config['type']) {
            case 'simple_moving_average':
              $data_json .= '[';
              $uri_count = 0;
              foreach ($data as $uri => $trend) {
                if ($uri_count != 0) {
                  $data_json .= ', ';
                }
                $data_json .= '{"label": "' . $trend['label'] . '", ';
                if (isset($colors[$uri_count])) {
                  $data_json .= '"color": "' . trim($colors[$uri_count]) . '", ';
                }
                $data_json .= '"data": [';
                $value_count = 0;
                $k = 9;
                $kk = ($k - 1) / 2;
                if (isset($trend['trend']) && !empty($trend['trend'])) {
                  $c = count($trend['trend']) - 1;
                  for ($t = $kk; $t <= $c - $kk; $t++) {
                    if ($value_count != 0) {
                      $data_json .= ', ';
                    }
                    $sum = 0;
                    for ($s = $t - $kk; $s <= $t + $kk; $s++) {
                      $sum += $trend['trend'][$s]['value'];
                    }
                    $data_json .= '[' . $trend['trend'][$t]['time'] . ', ' . $sum / $k . ']';
                    $value_count++;
                  }
                }
                else {
                  $data_json .= '[' . strtotime('-3 month +' . $kk . ' days') . '000, 0], [' . strtotime('-' . $kk . ' days') . '000, 0]';
                }
                $data_json .= ']}';
                $uri_count++;
              }
              $data_json .= ']';
              break;

            case 'raw_data':
              $data_json .= '[';
              $uri_count = 0;
              foreach ($data as $uri => $trend) {
                if ($uri_count != 0) {
                  $data_json .= ', ';
                }
                $data_json .= '{"label": "' . $trend['label'] . '", ';
                if (isset($colors[$uri_count])) {
                  $data_json .= '"color": "' . trim($colors[$uri_count]) . '", ';
                }
                $data_json .= '"data": [';
                $value_count = 0;
                if (isset($trend['trend']) && !empty($trend['trend'])) {
                  foreach ($trend['trend'] as $value_set) {
                    if ($value_count != 0) {
                      $data_json .= ', ';
                    }
                    $data_json .= '[' . $value_set['time'] . ', ' . $value_set['value'] . ']';
                    $value_count++;
                  }
                }
                else {
                  $data_json .= '[' . strtotime('-3 month') . '000, 0], [' . time() . '000, 0]';
                }
                $data_json .= ']}';
                $uri_count++;
              }
              $data_json .= ']';
              break;
          }
          $trends_output = array(
            '#theme' => 'pp_graphsearch_trends',
            '#data' => $data_json,
            '#config' => $config,
          );
        }

        $form['pp_graphsearch_filters']['trends'] = array(
          '#type' => 'item',
          '#prefix' => '<div id="edit-trends-wrapper" class="views-exposed-widget views-widget-filter-trends"><div class="views-widget">',
          '#suffix' => '</div></div>',
          '#markup' => (!empty($trends_output) ? \Drupal::service('renderer')
            ->render($trends_output) : ''),
          '#attached' => array(
            'library' => array(
              'flot/flot',
              'flot/time',
              'pp_graphsearch/trends'
            )
          ),
        );
      }
    }
  }

  /**
   * Returns the page URL where the PoolParty GraphSearch block is located.
   *
   * @return string
   *   The page URL to the PoolParty GraphSearch block.
   */
  public function getBlockPath() {
    $path = '';

    $block_query = \Drupal::entityQuery('block')
      ->condition('plugin', 'pp_graphsearch_block:' . $this->config->id());
    $block_ids = $block_query->execute();

    if (!empty($block_ids)) {
      $block = Block::load(reset($block_ids));
      $block_visibility = $block->getVisibility();

      $pages = explode(PHP_EOL, $block_visibility['request_path']['pages']);
      foreach ($pages as $page) {
        if (strpos($page, '*') === FALSE) {
          $path = $page;
          break;
        }
      }
    }

    return $path;
  }

  /**
   * Create a new PP GraphSearch configuration.
   *
   * @param string $title
   *   The title of the configuration.
   * @param string $search_space_id
   *   The ID of the search space.
   * @param string $connection_id
   *   The ID of Semantic Connector connection
   * @param array $config
   *   The config of the PP GraphSearch configuration as an array.
   *
   * @return PPGraphSearchConfig
   *   The new PP GraphSearch configuration.
   */
  public static function createConfiguration($title, $search_space_id, $connection_id, array $config = array()) {
    $configuration = PPGraphSearchConfig::create();
    $configuration->set('id', SemanticConnector::createUniqueEntityMachineName('pp_graphsearch', $title));
    $configuration->setTitle($title);
    $configuration->setSearchSpaceID($search_space_id);
    $configuration->setConnectionId($connection_id);
    $configuration->setConfig($config);
    $configuration->save();

    return $configuration;
  }

  /**
   * Get a single PoolParty GraphSearch agent.
   *
   * @param string $agent_id_full
   *   The full pipe-seperated ID of the agent containing
   *   - ID of the PP server connection
   *   - ID of the GraphSearch search space to use.
   *   - ID of the PoolParty GraphSearch agent
   *
   * @return array
   *   agent configuration array
   */
  public static function loadAgent($agent_id_full) {
    list($connection_id, $search_space_id, $agent_id) = explode('|', $agent_id_full);
    $pp_graphsearch_api = SemanticConnector::getConnection('pp_server', $connection_id)->getApi('sonr');
    $config = $pp_graphsearch_api->getAgent(urldecode($agent_id), $search_space_id);
    $config['connection_id'] = $connection_id;
    $config['search_space_id'] = $search_space_id;

    return $config;
  }

  /**
   * Returns all the taxonomies connected with the PoolParty GraphSearch.
   *
   * @param array $variables
   *   The variable settings of the connected taxonomies.
   * @param string $type
   *   The type of the result. The value can be empty, 'name' of 'machine_name'.
   *
   * @return array
   *   A list of the taxonomies.
   */
  public static function getAllConnectedTaxonomies($variables, $type='') {
    $taxonomies = array();
    foreach ($variables as $settings) {
      if ($settings['active']) {
        if (empty($type)) {
          $taxonomies = $taxonomies + $settings['taxonomies'];
        }
        else {
          foreach ($settings['taxonomies'] as $taxonomy) {
            $taxonomies[] = $taxonomy[$type];
          }
        }
      }
    }

    return array_unique($taxonomies);
  }

  /**
   * Update/delete nodes to/from a PoolParty GraphSearch server.
   *
   * @param array $ids
   *   Array of entity IDs to update/delete
   * @param string $entity_type
   *   The type of the entity (node | user).
   * @param array $info
   *   An associative array of information about the batch process.
   * @param array $context
   *   The Batch context to transmit data between different calls
   */
  public static function syncBatchProcess($ids, $entity_type, $info, &$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['operation'] = $info['operation'];
    }

    // Go through all entities and synchronize them.
    if ($entity_type == 'node') {
      $entities = \Drupal\node\Entity\Node::loadMultiple($ids);
    }
    else {
      $entities = User::loadMultiple($ids);
    }
    foreach ($entities as $entity) {
      $success = self::updateEntityPings($entity, $info['operation']);
      $context['results']['processed']++;
      if (!$success) {
        $context['results']['failed']++;
      }
    }

    $context['results']['end_time'] = time();

    // Show the remaining time as a batch message.
    $time_string = '';
    if ($context['results']['processed'] > 0) {
      $remaining_time = floor((time() - $info['start_time']) / $context['results']['processed'] * ($info['total'] - $context['results']['processed']));
      if ($remaining_time > 0) {
        $time_string = (floor($remaining_time / 86400)) . 'd ' . (floor($remaining_time / 3600) % 24) . 'h ' . (floor($remaining_time / 60) % 60) . 'm ' . ($remaining_time % 60) . 's';
      }
      else {
        $time_string = t('Done.');
      }
    }

    $context['message'] = t('Processed nodes: %processed of %total.', array(
        '%processed' => $context['results']['processed'],
        '%total' => $info['total']
      )) . '<br />' . t('Remaining time: %remainingtime.', array('%remainingtime' => $time_string));
  }

  /**
   * Batch 'finished' callback used by synchronization of nodes
   * with a PoolParty GraphSearch server.
   */
  public static function syncBatchFinished($success, $results, $operations) {
    if ($success) {
      if ($results['operation'] == 'update') {
        $message = t('Successfully finished synchronizing %total entities on %date:', array('%total' => $results['processed'],
            '%date' => \Drupal::service('date.formatter')
              ->format($results['end_time'], 'short')
          )) . '<br />';
      }
      else {
        $message = t('Successfully finished removing %total entities on %date:', array('%total' => $results['processed'],
            '%date' => \Drupal::service('date.formatter')
              ->format($results['end_time'], 'short')
          )) . '<br />';
      }

      $message .= t('<ul><li>failed: %failed</li></ul>', array('%failed' => $results['failed']));
      \Drupal::messenger()->addMessage($message);
    }
    else {
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation on %date', array(
        '%error_operation' => $error_operation[0],
        '%date' => \Drupal::service('date.formatter')
          ->format($results['end_time'], 'short'),
      )) . '<br />';
      $message .= t('<ul><li>arguments: %arguments</li></ul>', array(
      '@arguments' => print_r($error_operation[1], TRUE),
    ));
      \Drupal::messenger()->addMessage($message, 'error');
    }
  }

  /**
   * Perform an operation on the pings of a node.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The full node object to perform the operation on
   * @param string $operation
   *   The operation to perform --> can be "create", "update" or "delete"
   *
   * @return bool
   *   TRUE on success or if no operation was required, FALSE on failure
   */
  public static function updateEntityPings($entity, $operation) {
    $success = TRUE;
    $variables = \Drupal::config('pp_graphsearch.settings')->get('content_type_push');
    $entity_type = $entity->getEntityTypeId();
    $content_type = ($entity_type == 'node') ? $entity->getType() : 'user';
    $settings = isset($variables[$content_type]) ? $variables[$content_type] : array();

    // Check if the content type should be saved in the PoolParty GraphSearch also.
    if (empty($settings) || !$settings['active'] || !isset($settings['fields']) || empty($settings['fields'])) {
      return $success;
    }

    $graphsearch_connection = '';
    $graphsearch_config = array();
    if (!empty($settings['connection_id'])) {
      $pp_connection = SemanticConnector::getConnection('pp_server', $settings['connection_id']);
      $pp_config = $pp_connection->getConfig();

      if (!empty($pp_connection->id()) && isset($pp_config['graphsearch_configuration']) && !empty($pp_config['graphsearch_configuration'])) {
        $graphsearch_connection = $pp_connection;
        $graphsearch_config = $pp_config['graphsearch_configuration'];
      }
    }

    // Check if the PoolParty GraphSearch server is available.
    if (empty($graphsearch_connection)) {
      return $success;
    }

    // Check language.
    $entity_language = $entity->language();
    $search_spaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config, $settings['search_space_id'], '', $entity_language->getId());
    if (empty($search_spaces) && $entity_language->getId() != Language::LANGCODE_NOT_SPECIFIED) {
      return $success;
    }

    switch ($operation) {
      case 'delete':
        /** @var SemanticConnectorSonrApi $graphsearch_api */
        $graphsearch_api = $graphsearch_connection->getApi('sonr');
        $url = Url::fromRoute('entity.' . $entity_type . '.canonical', [$entity_type => $entity->id()], array('absolute' => TRUE, 'alias' => TRUE))->toString();
        $success = $graphsearch_api->deletePing($url);
        break;

      case 'create':
      case 'update':
        $entity_type_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $content_type);
        $entity_content = self::extractEntityHTML($entity, $settings['fields']);

        // Send content to PoolParty GraphSearch server.
        if (!empty($entity_content)) {
          // Node entities.
          if ($entity_type == 'node') {
            $ping = array(
              'title' => $entity->getTitle(),
              'text' => $entity_content,
              'username' => 'admin',
              'creationDate' => $entity->getCreatedTime() * 1000,
              'pageUrl' => Url::fromRoute('entity.' . $entity_type . '.canonical', [$entity_type => $entity->id()], array('absolute' => TRUE, 'alias' => TRUE))->toString(),
              'customAttributes' => array(
                SemanticConnectorSonrApi::ATTR_CONTENT_TYPE => [node_get_type_label($entity)],
              ),
              'spaceKey' => 'extern',
            );
          }
          // User entities.
          else {
            $ping = array(
              'title' => $entity_type->getAccountName(),
              'text' => $entity_content,
              'username' => 'admin',
              'creationDate' => $entity->getCreatedTime() * 1000,
              'pageUrl' => Url::fromRoute('entity.' . $entity_type . '.canonical', [$entity_type => $entity->id()], array('absolute' => TRUE, 'alias' => TRUE))->toString(),
              'customAttributes' => array(
                SemanticConnectorSonrApi::ATTR_CONTENT_TYPE => ['user'],
              ),
              'spaceKey' => 'extern',
            );
          }

          // Check for PowerTagging tags.
          $tag_uris = array();
          if (\Drupal::moduleHandler()->moduleExists('powertagging')) {
            // Check for the correct PoolParty server for safety reasons.
            $powertagging_configs = PowerTaggingConfig::loadConfigs($graphsearch_connection->id());
            /** @var PowerTaggingConfig $powertagging_config */
            foreach ($powertagging_configs as $powertagging_config) {
              if (isset($graphsearch_config['projects'][$powertagging_config->getProjectId()]) && isset($graphsearch_config['projects'][$powertagging_config->getProjectId()]['searchSpaces'][$settings['search_space_id']])) {
                continue;
              }
              /** @var \Drupal\Core\Field\BaseFieldDefinition $entity_type_field */
              foreach ($entity_type_fields as $field_name => $entity_type_field) {
                $field_values = $entity->{$field_name}->getValue();
                if ($entity_type_field->getType() != 'powertagging_tags' || $entity_type_field->getSetting('powertagging_id') != $powertagging_config->id() || empty($field_values)) {
                  continue;
                }
                // Load the tags.
                $tag_ids = [];
                foreach ($field_values as $tag_object) {
                  if (!empty($tag_object['target_id'])) {
                    $tag_ids[] = $tag_object['target_id'];
                  }
                }
                $tags = Term::loadMultiple($tag_ids);
                /** @var Term $tag */
                foreach ($tags as $tag) {
                  // Only add concepts, no freeterms.
                  $uri_value = $tag->get('field_uri')->getValue();
                  if (!empty($uri_value)) {
                    $tag_uris[] = $uri_value[0]['uri'];
                  }
                }
              }
              break;
            }
          }

          // Add available tags to the ping.
          if (!empty($tag_uris)) {
            $ping['dynUris'][SemanticConnectorSonrApi::ATTR_ALL_CONCEPTS] = $tag_uris;
          }
          else {
            $ping['dynUris'] = array();
          }

          // Check for selected taxonomies.
          $term_literals = array();
          if (isset($settings['taxonomies']) && !empty($settings['taxonomies'])) {
            // Get all content type fields referenced with taxonomy.
            $taxonomy_names = array();
            $taxonomies = self::getAllReferencedTaxonomies($content_type);
            foreach ($taxonomies as $field_name => $taxonomy) {
              $taxonomy_names[$taxonomy->id()] = array(
                'field_name' => $field_name,
                'taxonomy_name' => $taxonomy->id(),
              );
            }
            // Get all the selected terms and add it to the ping.
            foreach ($settings['taxonomies'] as $vid => $taxonomy) {
              if (isset($taxonomy_names[$vid])) {
                $field_name = $taxonomy_names[$vid]['field_name'];
                $custom_field = 'dyn_lit_' . $taxonomy_names[$vid]['taxonomy_name'];
                if (empty($entity->{$field_name})) {
                  continue;
                }
                $term_ids = array();
                foreach ($entity->get($field_name)->getValue() as $term_id) {
                  $term_ids[] = $term_id['tid'];
                }
                $terms = Term::loadMultiple($term_ids);
                $term_literals[$custom_field] = array();
                /** @var Term $term */
                foreach ($terms as $term) {
                  $term_literals[$custom_field][] = $term->getName();
                }
              }
            }
          }

          // Add available terms to the ping.
          if (!empty($term_literals)) {
            $ping['customAttributes'] = array_merge($ping['customAttributes'], $term_literals);
          }

          // Update the ping on the PoolParty GraphSearch server.
          $pp_graphsearch_api = $graphsearch_connection->getApi('sonr');
          switch ($operation) {
            case 'create':
              $success = $pp_graphsearch_api->createPing($ping, $settings['search_space_id']);
              break;

            case 'update':
              $success = $pp_graphsearch_api->updatePing($ping, $settings['search_space_id']);
              break;
          }
        }
        break;
    }

    return $success;
  }

  /**
   * Sorts a list of concept URIs according the order list.
   *
   * @param array $concept_uris
   *   The list of concept URIs.
   * @param array $facet_order
   *   The list according to which the concept URIs are to be sorted.
   * @param array $facet_map
   *   A map between the facet ID and its URI.
   *
   * @return array
   *   The sorted concept URIs.
   */
  protected function sortConceptUris($concept_uris, $facet_order, $facet_map) {
    $map = array();
    foreach ($concept_uris as $uri) {
      if (isset($facet_map[$uri])) {
        $map[$facet_map[$uri]] = $uri;
      }
    }

    return array_replace(array_intersect_key($facet_order, $map), $map);
  }

  /**
   * Clips the tree by max items and depth.
   * @param array $tree
   * @param int $max_items
   * @param int $depth
   * @return array
   */
  protected function clipTree($tree, $max_items, $depth) {
    if ($depth < 0 || empty($tree)) {
      return array();
    }

    foreach ($tree as $key => &$branch) {
      if (!empty($branch['children'])) {
        $branch['children'] = array_slice($branch['children'], 0, $max_items);
        $branch['children'] = $this->clipTree($branch['children'], $max_items, $depth-1);
      }
    }

    return $tree;
  }

  /**
   * Find out if the Flot library is installed.
   *
   * @return bool
   *   TRUE if the Visual Mapper exists, FALSE if not
   */
  public static function isFlotInstalled() {
    return \Drupal::moduleHandler()->moduleExists('flot');
  }

  /**
   * Returns all taxonomies referenced by the content type $content_type.
   *
   * @param string $content_type
   *   The content type
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary[]
   *   A list of taxonomy-objects.
   */
  public static function getAllReferencedTaxonomies($content_type) {
    $taxonomies = array();
    $field_config_storage = \Drupal::entityTypeManager()
      ->getStorage('field_config');
    $node_fields = $field_config_storage->loadByProperties(['entity_type' => 'node', 'bundle' => $content_type, 'field_type' => 'entity_reference']);
    $user_fields = $field_config_storage->loadByProperties(['entity_type' => 'user', 'field_type' => 'entity_reference']);
    $fields = array_merge($node_fields, $user_fields);

    /** @var \Drupal\field\Entity\FieldConfig $field */
    foreach ($fields as $field) {
      if ($field->getSetting('target_type') == 'taxonomy_term') {
        $handler_settings = $field->getSetting('handler_settings');
        $taxonomy_name = reset($handler_settings['target_bundles']);
        $taxonomy = \Drupal\taxonomy\Entity\Vocabulary::load($taxonomy_name);
        $taxonomies[$field->getName()] = $taxonomy;
      }
    }

    return $taxonomies;
  }

  /**
   * Disables the custom facet from all configuration sets.
   *
   * @param int $connection_id
   *   The connection ID to a PoolParty server.
   * @param int $vocabulary_id
   *   The ID of a vocabulary.
   */
  public static function disableCustomFacet($connection_id, $vocabulary_id) {
    // Load all configurations sets with the given connection ID.
    $config_query = \Drupal::entityQuery('pp_graphsearch');
    $config_query->condition('connection_id', $connection_id);
    $configuration_ids = $config_query->execute();
    $configuration_sets = PPGraphSearchConfig::loadMultiple($configuration_ids);
    $flush_cache = FALSE;

    /** @var PPGraphSearchConfig $configuration_set */
    foreach ($configuration_sets as $configuration_set) {
      $config = $configuration_set->getConfig();
      if (empty($config['facets_to_show'])) {
        continue;
      }
      foreach ($config['facets_to_show'] as &$facet) {
        $facet_id = 'dyn_lit_' . $vocabulary_id;
        if ($facet['facet_id'] == $facet_id) {
          $facet['selected'] = FALSE;

          $configuration_set->setConfig($config);
          $configuration_set->save();

          // Clear the cache for this configuration set.
          // @todo: Clear the cache here after the cache implementation is done + remove call to drupal_flush_all_caches().
          //$cache_id = 'semantic_connector:sonr_webmining:configuration_set_id:' . $configuration_set->getId();
          //cache_clear_all($cache_id, 'cache');
          $flush_cache = TRUE;
          break;
        }
      }
    }

    if ($flush_cache) {
      drupal_flush_all_caches();
    }
  }

  /**
   * Get the the fields that are supported for pushing to GraphSearch.
   *
   * @param string $entity_type
   *   The entity type to check.
   * @param string $bundle
   *   The bundle to check.
   *
   * @return array
   *   A list of supported fields.
   */
  public static function getSupportedPushFields($entity_type, $bundle) {
    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions($entity_type, $bundle);
    $widget_manager = \Drupal::service('plugin.manager.field.widget');

    $supported_field_types = [
      'core' => [
        'string' => ['string_textfield'],
        'string_long' => ['string_textarea'],
      ],
      'text' => [
        'text' => ['text_textfield'],
        'text_long' => ['text_textarea'],
        'text_with_summary' => ['text_textarea_with_summary'],
      ],
    ];
    $supported_fields = [];

    switch ($entity_type) {
      case 'node':
        break;

      case 'taxonomy_term':
        $supported_fields['description'] = t('Description') . '<span class="description">[' . t('Text area (multiple rows)') . ']</span>';
        break;

      case 'user':
        break;
    }

    // Get the form display to check which widgets are used.
    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.' . 'default');

    /** @var \Drupal\Core\Field\BaseFieldDefinition $field_definition */
    foreach ($field_definitions as $field_definition) {
      if (!$field_definition instanceof FieldConfig) {
        continue;
      }

      $field_storage = $field_definition->getFieldStorageDefinition();
      $specific_widget_type = $form_display->getComponent($field_definition->getName());
      if (isset($supported_field_types[$field_storage->getTypeProvider()][$field_storage->getType()]) && in_array($specific_widget_type['type'], $supported_field_types[$field_storage->getTypeProvider()][$field_storage->getType()])) {
        $widget_info = $widget_manager->getDefinition($specific_widget_type['type']);
        $supported_fields[$field_definition->getName()] = $field_definition->label() . '<span class="description">[' . $widget_info['label'] . ']</span>';
      }
    }

    return $supported_fields;
  }

  /**
   * Extract the HTML content of an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The entity to extract the content for.
   * @param array $fields
   *   An array of tag field IDs
   *
   * @return string
   *   The extracted text from the entity.
   */
  public static function extractEntityHTML($entity, $fields) {
    $entity_content = '';
    $text_parts = [];
    foreach ($fields as $tag_field_name) {
      if (!$entity->hasField($tag_field_name) ||
        $entity->get($tag_field_name)->count() == 0
      ) {
        continue;
      }

      foreach ($entity->get($tag_field_name)->getValue() as $value) {
        $tag_content = trim(strip_tags(isset($value['value']) ? $value['value'] : (isset($value['uri']) ? $value['uri'] : '')));
        if (isset($value['summary'])) {
          $tag_summary = trim(strip_tags($value['summary']));
          if (!empty($tag_summary) && $tag_summary != $tag_content) {
            $tag_content = $tag_summary;
          }
        }
        if (!empty($tag_content)) {
          $text_parts[] = $tag_content;
        }
      }
    }

    if (!empty($text_parts)) {
      $entity_content = implode(' ', $text_parts);
    }

    return $entity_content;
  }

  /**
   * Formats an RSS channel.
   *
   * Arbitrary elements may be added using the $args associative array.
   */
  private function format_rss_channel($title, $link, $description, $items, $langcode = NULL, $args = array()) {
    $langcode = $langcode ? $langcode : \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

    $output = "<channel>\n";
    $output .= ' <title>' . Html::escape($title) . "</title>\n";
    $output .= ' <link>' . UrlHelper::filterBadProtocol($link) . "</link>\n";

    // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
    // We strip all HTML tags, but need to prevent double encoding from properly
    // escaped source data (such as &amp becoming &amp;amp;).
    $output .= ' <description>' . Html::escape(Html::decodeEntities(strip_tags($description))) . "</description>\n";
    $output .= ' <language>' . Html::escape($langcode) . "</language>\n";
    $output .= $this->format_xml_elements($args);
    $output .= $items;
    $output .= "</channel>\n";

    return $output;
  }

  /**
   * Formats a single RSS item.
   *
   * Arbitrary elements may be added using the $args associative array.
   */
  private function format_rss_item($title, $link, $description, $args = array()) {
    $output = "<item>\n";
    $output .= ' <title>' . Html::escape($title) . "</title>\n";
    $output .= ' <link>' . UrlHelper::filterBadProtocol($link) . "</link>\n";
    $output .= ' <description>' . Html::escape($description) . "</description>\n";
    $output .= $this->format_xml_elements($args);
    $output .= "</item>\n";

    return $output;
  }

  /**
   * Formats XML elements.
   *
   * @param $array
   *   An array where each item represents an element and is either a:
   *   - (key => value) pair (<key>value</key>)
   *   - Associative array with fields:
   *     - 'key': element name
   *     - 'value': element contents
   *     - 'attributes': associative array of element attributes
   *     - 'encoded': TRUE if 'value' is already encoded
   *
   * In both cases, 'value' can be a simple string, or it can be another array
   * with the same format as $array itself for nesting.
   *
   * If 'encoded' is TRUE it is up to the caller to ensure that 'value' is either
   * entity-encoded or CDATA-escaped. Using this option is not recommended when
   * working with untrusted user input, since failing to escape the data
   * correctly has security implications.
   *
   * @return string
   *   The HTML output
   */
  private function format_xml_elements($array) {
    $output = '';
    foreach ($array as $key => $value) {
      if (is_numeric($key)) {
        if ($value['key']) {
          $output .= ' <' . $value['key'];
          if (isset($value['attributes']) && is_array($value['attributes'])) {
            $output .= new \Drupal\Core\Template\Attribute($value['attributes']);
          }

          if (isset($value['value']) && $value['value'] != '') {
            $output .= '>' . (is_array($value['value']) ? $this->format_xml_elements($value['value']) : (!empty($value['encoded']) ? $value['value'] : Html::escape($value['value']))) . '</' . $value['key'] . ">\n";
          }
          else {
            $output .= " />\n";
          }
        }
      }
      else {
        $output .= ' <' . $key . '>' . (is_array($value) ? $this->format_xml_elements($value) : Html::escape($value)) . "</$key>\n";
      }
    }
    return $output;
  }
}