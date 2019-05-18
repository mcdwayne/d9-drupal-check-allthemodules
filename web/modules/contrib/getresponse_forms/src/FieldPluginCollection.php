<?php

namespace Drupal\getresponse_forms;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of GetResponse fields.
 */
class FieldPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\getresponse_forms\FieldInterface
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
      return 0;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

}
