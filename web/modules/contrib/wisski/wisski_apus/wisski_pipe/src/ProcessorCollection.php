<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\ProcessorCollection.
 */

namespace Drupal\wisski_pipe;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of processor plugins.
 */
class ProcessorCollection extends DefaultLazyPluginCollection {

  /**
   * All possible processor IDs.
   *
   * @var array
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\wisski_pipe\ProcessorInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($aID, $bID) {
    $a_weight = $this->get($aID)->getWeight();
    $b_weight = $this->get($bID)->getWeight();
    if ($a_weight == $b_weight) {
      return strnatcasecmp($this->get($aID)->getLabel(), $this->get($bID)->getLabel());
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

}
