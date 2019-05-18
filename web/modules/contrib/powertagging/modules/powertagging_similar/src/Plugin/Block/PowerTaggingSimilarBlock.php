<?php

namespace Drupal\powertagging_similar\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\powertagging_similar\Entity\PowerTaggingSimilarConfig;
use Drupal\powertagging_similar\PowerTaggingSimilar;

/**
 * Provides a 'PowerTaggingSimilarBlock' block plugin.
 *
 * @Block(
 *   id = "powertagging_similar_block",
 *   admin_label = @Translation("PowerTagging SeeAlso widget"),
 *   deriver = "Drupal\powertagging_similar\Plugin\Derivative\PowerTaggingSimilarBlock"
 * )
 */

class PowerTaggingSimilarBlock extends BlockBase {

  /**
   * @var PowerTaggingSimilarConfig.
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
    $this->config = PowerTaggingSimilarConfig::load($this->getDerivativeId());
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

    if ($this->config) {
      // Get the currently viewed entity type and entity ID.
      $entity_type = '';
      $entity_id = '';
      $current_path = \Drupal\Core\Url::fromUserInput(\Drupal::service('path.current')->getPath());
      $params = array();
      if ($current_path->isRouted()) {
        $params = $current_path->getRouteParameters();
      }

      foreach (array('node', 'user', 'taxonomy_term') as $current_entity_type) {
        if (isset($params[$current_entity_type])) {
          $entity_type = $current_entity_type;
          $entity_id = $params[$current_entity_type];
        }
      }

      if (!empty($entity_type) && !is_null($entity_id)) {
        $powertagging_similar = new PowerTaggingSimilar($this->config);

        // Create the block with the similar contents.
        $block['content'] = array(
          '#markup' => $powertagging_similar->displayWidget($entity_type, $entity_id),
        );

        $block['#contextual_links'] = array(
          'powertagging_similar' => array(
            'route_parameters' => array('powertagging_similar' => $this->config->id()),
          ),
        );
      }
    }

    return $block;
  }
}
