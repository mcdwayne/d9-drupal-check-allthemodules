<?php

namespace Drupal\ga_server_events;

class Event {

  /**
   * @var string
   */
  protected $hitType = '';

  /**
   * @var string
   */
  protected $eventCategory = '';

  /**
   * @var string
   */
  protected $eventAction = '';

  /**
   * @var string
   */
  protected $eventLabel = '';

  /**
   * @return string
   */
  public function getHitType() {
    return $this->hitType;
  }

  /**
   * @param string $hitType
   */
  public function setHitType($hitType) {
    $this->hitType = $hitType;
  }

  /**
   * @return string
   */
  public function getEventCategory() {
    return $this->eventCategory;
  }

  /**
   * @param string $eventCategory
   */
  public function setEventCategory($eventCategory) {
    $this->eventCategory = $eventCategory;
  }

  /**
   * @return string
   */
  public function getEventAction() {
    return $this->eventAction;
  }

  /**
   * @param string $eventAction
   */
  public function setEventAction($eventAction) {
    $this->eventAction = $eventAction;
  }

  /**
   * @return string
   */
  public function getEventLabel() {
    return $this->eventLabel;
  }

  /**
   * @param string $eventLabel
   */
  public function setEventLabel($eventLabel) {
    $this->eventLabel = $eventLabel;
  }

  public static function createFromValues(array $values) {
    $e = new self();
    foreach ($values as $key => $value) {
      if (!property_exists(self::class, $key)) {
        throw new \InvalidArgumentException('No such GA property: ' . $key);
      }
      $e->{$key} = $value;
    }
    return $e;
  }

  public function getScript() {
    return sprintf("ga('send', {
      hitType: '%s',
      eventCategory: '%s',
      eventAction: '%s',
      eventLabel: '%s'
    });", $this->getHitType(), $this->getEventCategory(), $this->getEventAction(), $this->getEventLabel());
  }
}
