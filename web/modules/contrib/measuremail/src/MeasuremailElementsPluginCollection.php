<?php

namespace Drupal\measuremail;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of measuremail elements.
 */
class MeasuremailElementsPluginCollection extends DefaultLazyPluginCollection {

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

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\measuremail\MeasuremailElementsInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
