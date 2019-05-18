<?php

namespace Drupal\feeds_tamper;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * Collection of Tamper plugins for a specific Feeds importer.
 */
class TamperPluginCollection extends DefaultLazyPluginCollection {

  /**
   * The key within the plugin configuration that contains the plugin ID.
   *
   * @var string
   */
  protected $pluginKey = 'plugin';

  /**
   * Provides uasort() callback to sort plugins.
   */
  public function sortHelper($aID, $bID) {
    $a = $this->get($aID);
    $b = $this->get($bID);

    if ($a->getSetting('weight') != $b->getSetting('weight')) {
      return $a->getSetting('weight') < $b->getSetting('weight') ? -1 : 1;
    }

    return parent::sortHelper($aID, $bID);
  }

}
