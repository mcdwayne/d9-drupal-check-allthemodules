<?php

namespace Drupal\prepared_data\Plugin\prepared_data\Processor;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\prepared_data\PreparedDataInterface;
use Drupal\prepared_data\Processor\ProcessorBase;

/**
 * Datetime processor class.
 *
 * @PreparedDataProcessor(
 *   id = "datetime",
 *   label = @Translation("Date and time generator"),
 *   weight = 10,
 *   manageable = true
 * )
 */
class Datetime extends ProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function process(PreparedDataInterface $data) {
    $now = (new DrupalDateTime('now'))->setTimezone(new \DateTimeZone('UTC'));
    $data->data()['datetime'] = [
      'formatted' => $now->format('c'),
      'stamp' => $now->getTimestamp(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(PreparedDataInterface $data) {
    if (!$this->isEnabled()) {
      $data_array = &$data->data();
      unset($data_array['datetime']);
    }
  }

}
