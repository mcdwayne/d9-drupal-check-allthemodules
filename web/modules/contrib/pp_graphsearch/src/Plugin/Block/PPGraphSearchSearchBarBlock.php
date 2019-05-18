<?php

namespace Drupal\pp_graphsearch\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\pp_graphsearch\Entity\PPGraphSearchConfig;
use Drupal\pp_graphsearch\PPGraphSearch;

/**
 * Provides a 'PPGraphSearchBarBlock' block plugin.
 *
 * @Block(
 *   id = "pp_graphsearch_search_bar_block",
 *   admin_label = @Translation("PoolParty GraphSearch Search Bar"),
 *   deriver = "Drupal\pp_graphsearch\Plugin\Derivative\PPGraphSearchSearchBarBlock"
 * )
 */

class PPGraphSearchSearchBarBlock extends BlockBase {

  /**
   * @var PPGraphSearchConfig.
   */
  private $graphsearch_config;

  /**
   * Creates a NodeBlock instance.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->graphsearch_config = PPGraphSearchConfig::load($this->getDerivativeId());
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = array(
      '#markup' => '',
    );

    // If the block is one of the PoolParty GraphSearch then call the search or
    // get it from the cache.
    if ($this->graphsearch_config) {
      $config = $this->graphsearch_config->getConfig();

      if (isset($config['show_block_searchbar']) && $config['show_block_searchbar']) {
        $graphsearch = new PPGraphSearch($this->graphsearch_config);
        $path = $graphsearch->getBlockPath();

        $block['#attached']['drupalSettings']['pp_graphsearch'] = array(
          'min_chars' => $config['ac_min_chars'],
          'add_matching_label' => $config['ac_add_matching_label'],
          'add_context' => $config['ac_add_context'],
          'search_type' => $config['search_type'],
          'page_path' => $path,
        );

        // Add JS.
        $block['#attached']['library'] = array(
          'pp_graphsearch/search_bar',
        );

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $block['form'] = \Drupal::formBuilder()->getForm('\Drupal\pp_graphsearch\Form\PPGraphSearchSearchBarForm', $graphsearch, TRUE);
      }
    }

    return $block;
  }
}
