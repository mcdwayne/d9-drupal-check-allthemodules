<?php

namespace Drupal\dropshark\Plugin\DropShark\Collector\Drupal;

use Drupal\dropshark\Collector\CollectorBase;
use Drupal\dropshark\Collector\CollectorInterface;

/**
 * Class DrupalStatusCollector.
 *
 * @DropSharkCollector(
 *   id = "drupal_status",
 *   title = @Translation("Drupal Status"),
 *   description = @Translation("Drupal status report information."),
 *   events = {"drupal"}
 * )
 */
class DrupalStatusCollector extends CollectorBase {

  /**
   * {@inheritdoc}
   */
  public function collect(array $data = []) {
    $data = $this->defaultResult();

    // Load .install files.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    drupal_load_updates();

    // Check run-time requirements and status information.
    $requirements = $this->getModuleHandler()
      ->invokeAll('requirements', ['runtime']);
    ksort($requirements);

    // Count number of requirements at each status.
    $severities = [
      'error' => 0,
      'info' => 0,
      'ok' => 0,
      'warning' => 0,
      'none' => 0,
    ];
    $severities_map = [
      REQUIREMENT_ERROR => 'error',
      REQUIREMENT_INFO => 'info',
      REQUIREMENT_OK => 'ok',
      REQUIREMENT_WARNING => 'warning',
      'none' => 0,
    ];
    foreach ($requirements as $requirement) {
      if (isset($requirement['severity'])) {
        $key = $severities_map[$requirement['severity']];
        $severities[$key]++;
      }
      else {
        $severities['none']++;
      }
    }

    $data['code'] = CollectorInterface::STATUS_SUCCESS;
    $data['requirements'] = $requirements;
    $data['severity'] = $severities;
    $this->getQueue()->add($data);
  }

}
