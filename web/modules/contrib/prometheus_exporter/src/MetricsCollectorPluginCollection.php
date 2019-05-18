<?php

namespace Drupal\prometheus_exporter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of metrics collector plugins.
 */
class MetricsCollectorPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    $configuration = $this->manager->getDefinition($instance_id);
    // Merge the actual configuration into the default configuration.
    if (isset($this->configurations[$instance_id])) {
      $configuration = NestedArray::mergeDeep($configuration, $this->configurations[$instance_id]);
    }
    $this->configurations[$instance_id] = $configuration;
    parent::initializePlugin($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($aID, $bID) {
    $a = $this->get($aID);
    $b = $this->get($bID);
    if ($a->isEnabled() != $b->isEnabled()) {
      return !empty($a->isEnabled()) ? -1 : 1;
    }
    if ($a->getWeight() != $b->getWeight()) {
      return $a->getWeight() < $b->getWeight() ? -1 : 1;
    }
    if ($a->getProvider() != $b->getProvider()) {
      return strnatcasecmp($a->getProvider(), $b->getProvider());
    }
    return parent::sortHelper($aID, $bID);
  }

}
