<?php

/**
 * @file
 * Contains \Drupal\powertagging_similar\Plugin\Derivative\PowerTaggingSimilarBlock.
 */

namespace Drupal\powertagging_similar\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\powertagging_similar\Entity\PowerTaggingSimilarConfig;

/**
 * Provides block plugin definitions for nodes.
 *
 * @see \Drupal\powertagging_similar\Plugin\Block\PowerTaggingBlock
 */
class PowerTaggingSimilarBlock extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $powertagging_configs = PowerTaggingSimilarConfig::loadMultiple();

    /** @var PowerTaggingSimilarConfig $powertagging_similar_config */
    foreach ($powertagging_configs as $powertagging_similar_config) {
      $this->derivatives[$powertagging_similar_config->id()] = $base_plugin_definition;
      $this->derivatives[$powertagging_similar_config->id()]['admin_label'] = t('PowerTagging SeeAlso widget "@configtitle"', array('@configtitle' => $powertagging_similar_config->getTitle()));
    }
    return $this->derivatives;
  }
}
