<?php

namespace Drupal\dropshark\Plugin\DropShark\Collector\System;

use Drupal\dropshark\Collector\CollectorInterface;

/**
 * Class DiskCollector.
 *
 * @DropSharkCollector(
 *   id = "disk",
 *   title = @Translation("Disk"),
 *   description = @Translation("Disk utilization information."),
 *   events = {"system"}
 * )
 */
class DiskCollector extends LinfoCollector {

  /**
   * {@inheritdoc}
   */
  public function collect(array $data = []) {
    $data = $this->defaultResult();

    if (!$this->checkLinfo($data)) {
      return;
    }

    if (!$disk = $this->defaultDisk()) {
      $data['code'] = 'unable_to_determine_disk';
      $this->getQueue()->add($data);
      return;
    }
    $data['ds_collector_id'] .= "|{$disk}";
    $disks = $this->getData();
    $disk_info = $disks[$disk];
    $data['code'] = CollectorInterface::STATUS_SUCCESS;
    $data['device'] = $disk;
    $data['mount'] = $disk;
    $data['free'] = $disk_info['free'];
    $data['used'] = $disk_info['used'];
    $data['size'] = $data['free'] + $data['used'];
    $data['used_percent'] = $data['used'] / $data['size'];
    $this->getQueue()->add($data);
  }

  /**
   * Gets information for the disks on the machine.
   *
   * @return array
   *   Data representing the existence and utilization of disks on the machine.
   */
  public function getData() {
    static $data = NULL;
    if ($data === NULL) {
      $data = [];
      foreach ($this->getLinfo()->getParser()->getMounts() as $mount) {
        $data[$mount['mount']] = $mount;
      }
    }
    return $data;
  }

  /**
   * Lists disks on the machine.
   *
   * @return array
   *   Mount points for disks on the machine.
   */
  public function getDisks() {
    return array_keys($this->getData());
  }

  /**
   * Determine which disk is the default.
   *
   * Used where configuration data is not present or appears to be invalid.
   */
  public function defaultDisk() {
    // Look for exact match.
    if (array_search(DRUPAL_ROOT, $this->getDisks()) !== FALSE) {
      return DRUPAL_ROOT;
    }
    // Iterate over the disks, look for the most-specific possibility.
    $match = NULL;
    foreach ($this->getDisks() as $disk) {
      if (strpos(DRUPAL_ROOT, $disk) === 0) {
        // If there is no existing match, or we've found a more specific match,
        // use it.
        if (!$match || strlen($disk) > strlen($match)) {
          $match = $disk;
        }
      }
    }
    return $match;
  }

}
