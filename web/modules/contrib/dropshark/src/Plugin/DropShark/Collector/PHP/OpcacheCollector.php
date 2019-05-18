<?php

namespace Drupal\dropshark\Plugin\DropShark\Collector\PHP;

use Drupal\dropshark\Collector\CollectorBase;
use Drupal\dropshark\Collector\CollectorInterface;

/**
 * Class OpcacheCollector.
 *
 * @DropSharkCollector(
 *   id = "opcache",
 *   title = @Translation("Opcache"),
 *   description = @Translation("PHP Opcache utilization information."),
 *   events = {"system"}
 * )
 */
class OpcacheCollector extends CollectorBase {

  /**
   * {@inheritdoc}
   */
  public function collect(array $data = []) {
    $data = $this->defaultResult();

    if (!function_exists('opcache_get_status')) {
      $data['code'] = 'no_opcache';
      $this->getQueue()->add($data);
      return;
    }

    if (!$status = opcache_get_status(FALSE)) {
      $data['code'] = 'opcache_not_enabled';
      $this->getQueue()->add($data);
      return;
    }

    $data += $status;
    $data['code'] = CollectorInterface::STATUS_SUCCESS;

    $this->getQueue()->add($data);
  }

}
