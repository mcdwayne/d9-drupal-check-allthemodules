<?php

namespace Drupal\pp_graphsearch\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\pp_graphsearch\Entity\PPGraphSearchConfig;
use Drupal\pp_graphsearch\PPGraphSearch;

/**
 * Provides a 'PPGraphSearchFilterBlock' block plugin.
 *
 * @Block(
 *   id = "pp_graphsearch_filter_block",
 *   admin_label = @Translation("PoolParty GraphSearch Filters"),
 *   deriver = "Drupal\pp_graphsearch\Plugin\Derivative\PPGraphSearchFilterBlock"
 * )
 */
class PPGraphSearchFilterBlock extends BlockBase {

  /**
   * @var PPGraphSearchConfig.
   */
  private $graphsearchConfig;

  /**
   * Creates a NodeBlock instance.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->graphsearchConfig = PPGraphSearchConfig::load($this->getDerivativeId());
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = array(
      '#cache' => array(
        'max-age' => 0
      )
    );

    // Disable the internal page cache for anonymous users.
    \Drupal::service('page_cache_kill_switch')->trigger();

    // If the block is one of the PoolParty GraphSearch then call the search or
    // get it from the cache.
    if ($this->graphsearchConfig) {
      //$config = $this->graphsearchConfig->getConfig();
      $graphsearches = &drupal_static('pp_graphsearch_block_graphsearches', array());
      if (empty($graphsearches) || !isset($graphsearches[$this->graphsearchConfig->id()])) {
        $graphsearch = new PPGraphSearch($this->graphsearchConfig);
        $filters = $graphsearch->getFiltersFromUrlParameter();

        // Load the PoolParty GraphSearch object with the result list from
        // the cache if no filter is given.
        /*if ($config['cache_lifetime'] && empty($filters)) {
          $cache_id = 'semantic_connector:sonr_webmining:configuration_set_id:' . $configuration_set_id;
          if ($cache = cache_get($cache_id)) {
            $aggregator = $cache->data;
          }
          // Set the cache data if the API connection is available.
          elseif ($aggregator->availableApi()) {
            $aggregator->search();
            cache_set($cache_id, $aggregator, 'cache', time() + $config['cache_lifetime']);
          }
        }
        // Else set the filters and start searching.
        else { */
        $graphsearch->setFilters($filters);
        $graphsearch->search();
        // }

        $graphsearches[$this->graphsearchConfig->id()] = $graphsearch;
      }
      else {
        $graphsearch = $graphsearches[$this->graphsearchConfig->id()];
      }

      // Create the block output.
      $block['content'] = array(
        'filters' => $graphsearch->themeFilters(),
      );

      $block['#contextual_links'] = array(
        'pp_graphsearch' => array(
          'route_parameters' => array('pp_graphsearch' => $this->graphsearchConfig->id()),
        ),
      );
    }

    return $block;
  }
}
