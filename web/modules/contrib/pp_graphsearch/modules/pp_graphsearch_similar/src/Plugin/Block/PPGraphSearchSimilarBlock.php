<?php

namespace Drupal\pp_graphsearch_similar\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\pp_graphsearch_similar\Entity\PPGraphSearchSimilarConfig;
use Drupal\pp_graphsearch_similar\PPGraphSearchSimilar;

/**
 * Provides a 'PPGraphSearchSimilarBlock' block plugin.
 *
 * @Block(
 *   id = "pp_graphsearch_similar_block",
 *   admin_label = @Translation("PoolParty GraphSearch SeeAlso widget"),
 *   deriver = "Drupal\pp_graphsearch_similar\Plugin\Derivative\PPGraphSearchSimilarBlock"
 * )
 */

class PPGraphSearchSimilarBlock extends BlockBase {

  /**
   * @var PPGraphSearchSimilarConfig.
   */
  private $config;

  /**
   * Creates a NodeBlock instance.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = PPGraphSearchSimilarConfig::load($this->getDerivativeId());
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

    // If the block is one of the PoolParty GraphSearch then call the search or
    // get it from the cache.
    if ($this->config) {
      // Check if a node is displayed.
      $current_path = \Drupal\Core\Url::fromUserInput(\Drupal::service('path.current')->getPath());
      $params = array();
      if ($current_path->isRouted()) {
        $params = $current_path->getRouteParameters();
      }

      if (!isset($params['node'])) {
        return $block;
      }

      $node = \Drupal\node\Entity\Node::load($params['node']);

      $pp_graphsearch_similar = new PPGraphSearchSimilar($this->config);
      $similar_documents = array();

      // Check if the node (the content type) is stored in PoolParty GraphSearch
      // server.
      $push_data = \Drupal::config('pp_graphsearch.settings')->get('content_type_push');
      if (isset($push_data[$node->getType()]) && $push_data[$node->getType()]['connection_id'] == $this->config->getConnection()->getId()) {
        $url = $GLOBALS['base_url'] . '/' . \Drupal::service('path.current')->getPath();
        $similar_documents = $pp_graphsearch_similar->fromUrl($url);
      }

      // Check if the node has PowerTagging Tags.
      if (empty($similar_documents) && \Drupal::service('module_handler')->moduleExists('powertagging')) {
        $similar_documents = $pp_graphsearch_similar->fromTags($node);
      }

      // Get similar documents from the content of the node.
      if (empty($similar_documents)) {
        $similar_documents = $pp_graphsearch_similar->fromContent($node);
      }

      // Create the block with the similar contents.
      if (!empty($similar_documents)) {
        $list = '<div class="item-list"><ul><li>' . implode('</li><li>', $similar_documents) . '</li></ul></div>';
        $block['content'] =  array(
          '#markup' => $list,
        );

        $block['#contextual_links'] = array(
          'pp_graphsearch_similar' => array(
            'route_parameters' => array('pp_graphsearch_similar' => $this->config->id()),
          ),
        );
      }
    }

    return $block;
  }
}
