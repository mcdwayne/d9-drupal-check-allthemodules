<?php
/**
 * @file
 * Contains \Drupal\smart_ip\GetLocationEvent.
 */

namespace Drupal\smart_ip;

use Symfony\Component\EventDispatcher\Event;

/**
 * Provides Smart IP query location override event for event listeners.
 *
 * @package Drupal\smart_ip
 */
class GetLocationEvent extends Event {

  /**
   * Contains user's location.
   *
   * @var \Drupal\smart_ip\SmartIpLocationInterface
   */
  protected $location;

  /**
   * Contains Smart IP data source info.
   *
   * @var string
   */
  protected $dataSource;

  /**
   * Constructs a Smart IP event.
   *
   * @param \Drupal\smart_ip\SmartIpLocationInterface $location
   *   Smart IP's data location.
   */
  public function __construct(SmartIpLocationInterface $location) {
    $this->setLocation($location);
    $this->dataSource = \Drupal::config('smart_ip.settings')->get('data_source');
  }

  /**
   * Get Smart IP's data location.
   *
   * @return \Drupal\smart_ip\SmartIpLocationInterface
   *   Smart IP's data location.
   */
  public function getLocation() {
    return $this->location;
  }

  /**
   * Set Smart IP's data location.
   *
   * @param \Drupal\smart_ip\SmartIpLocationInterface $location
   *   Smart IP's data location.
   */
  public function setLocation(SmartIpLocationInterface $location) {
    $this->location = $location;
  }

  /**
   * Get Smart IP's data source.
   *
   * @return string
   *   Smart IP's data source.
   */
  public function getDataSource() {
    return $this->dataSource;
  }

}
