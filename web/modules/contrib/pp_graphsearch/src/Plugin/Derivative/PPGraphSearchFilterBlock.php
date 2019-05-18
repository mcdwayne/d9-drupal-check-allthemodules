<?php

/**
 * @file
 * Contains \Drupal\pp_graphsearch\Plugin\Derivative\PPGraphSearchFilterBlock.
 */

namespace Drupal\pp_graphsearch\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\pp_graphsearch\Entity\PPGraphSearchConfig;

/**
 * Provides block plugin definitions for nodes.
 *
 * @see \Drupal\pp_graphsearch\Plugin\Block\PPGraphSearchFilterBlock
 */
class PPGraphSearchFilterBlock extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $graphsearch_configs = PPGraphSearchConfig::loadMultiple();

    /** @var PPGraphSearchConfig $graphsearch_config */
    foreach ($graphsearch_configs as $graphsearch_config) {
      $config_settings = $graphsearch_config->getConfig();
      if ($config_settings['separate_blocks']) {
        $this->derivatives[$graphsearch_config->id()] = $base_plugin_definition;
        $this->derivatives[$graphsearch_config->id()]['admin_label'] = t('PoolParty GraphSearch Filters for "@configtitle"', array('@configtitle' => $graphsearch_config->getTitle()));
      }
    }
    return $this->derivatives;
  }
}
