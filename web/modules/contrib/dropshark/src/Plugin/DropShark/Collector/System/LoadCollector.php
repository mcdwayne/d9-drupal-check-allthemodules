<?php

namespace Drupal\dropshark\Plugin\DropShark\Collector\System;

use Drupal\dropshark\Collector\CollectorInterface;

/**
 * Class LoadCollector.
 *
 * @DropSharkCollector(
 *   id = "load",
 *   title = @Translation("CPU Load"),
 *   description = @Translation("CPU Load information."),
 *   events = {"system"}
 * )
 */
class LoadCollector extends LinfoCollector {

  /**
   * {@inheritdoc}
   */
  public function collect(array $data = []) {
    $data = $this->defaultResult();

    if (!$this->checkLinfo($data)) {
      return;
    }

    if (!$load = $this->getLinfo()->getParser()->getLoad()) {
      $data['code'] = 'unable_to_determine_load';
      $this->getQueue()->add($data);
      return;
    }

    $data = array_merge($data, $load);
    $data['code'] = CollectorInterface::STATUS_SUCCESS;
    $this->getQueue()->add($data);
  }

}
