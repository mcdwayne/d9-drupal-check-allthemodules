<?php

namespace Drupal\dropshark\Plugin\DropShark\Collector\System;

use Drupal\dropshark\Collector\CollectorInterface;

/**
 * Class SwapCollector.
 *
 * @DropSharkCollector(
 *   id = "swap",
 *   title = @Translation("Swap"),
 *   description = @Translation("Swap usage information."),
 *   events = {"system"}
 * )
 */
class SwapCollector extends MemoryCollector {

  /**
   * {@inheritdoc}
   */
  public function collect(array $data = []) {
    $data = $this->defaultResult();

    if (!$this->checkLinfo($data)) {
      return;
    }

    $memory = $this->getData();

    if (empty($memory['swapInfo'][0])) {
      $data['code'] = 'unable_to_determine_swap';
      $this->getQueue()->add($data);
      return;
    }

    $swap = $memory['swapInfo'][0];

    $data['code'] = CollectorInterface::STATUS_SUCCESS;
    $data['used'] = $swap['used'];
    $data['size'] = $swap['size'];
    $data['used_percent'] = $swap['size'] ? $swap['used'] / $swap['size'] : NULL;
    $this->getQueue()->add($data);
  }

}
