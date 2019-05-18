<?php

/**
 * @file
 * Contains \Drupal\pp_graphsearch_similar\Plugin\Derivative\PPGraphSearchSimilarBlock.
 */

namespace Drupal\pp_graphsearch_similar\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\pp_graphsearch_similar\Entity\PPGraphSearchSimilarConfig;

/**
 * Provides block plugin definitions for nodes.
 *
 * @see \Drupal\pp_graphsearch\Plugin\Block\PPGraphSearchBlock
 */
class PPGraphSearchSimilarBlock extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $graphsearch_configs = PPGraphSearchSimilarConfig::loadMultiple();

    /** @var PPGraphSearchSimilarConfig $graphsearch_similar_config */
    foreach ($graphsearch_configs as $graphsearch_similar_config) {
      $this->derivatives[$graphsearch_similar_config->id()] = $base_plugin_definition;
      $this->derivatives[$graphsearch_similar_config->id()]['admin_label'] = t('PoolParty GraphSearch SeeAlso widget "@configtitle"', array('@configtitle' => $graphsearch_similar_config->getTitle()));
    }
    return $this->derivatives;
  }
}
