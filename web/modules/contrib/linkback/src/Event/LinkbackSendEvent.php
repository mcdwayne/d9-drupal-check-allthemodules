<?php

namespace Drupal\linkback\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Url;

/**
 * Event that is fired when a linkback needs to be send.
 *
 * @see linkback_send()
 */
class LinkbackSendEvent extends Event {

  const EVENT_NAME = 'linkback_send';

  /**
   * The source url.
   *
   * @var \Drupal\Core\Url
   */
  protected $source;

  /**
   * The target url.
   *
   * @var \Drupal\Core\Url
   */
  protected $target;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Url $source
   *   The source Url.
   * @param \Drupal\Core\Url $target
   *   The target Url.
   */
  public function __construct(Url $source, Url $target) {
    $this->source = $source;
    $this->target = $target;
  }

  /**
   * Getter for the source Url.
   *
   * @return \Drupal\Core\Url
   *   The source Url.
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Setter for the source Url.
   *
   * @param \Drupal\Core\Url $source
   *   The source Url.
   */
  public function setSource(Url $source) {
    $this->source = $source;
  }

  /**
   * Getter for the target Url.
   *
   * @return \Drupal\Core\Url
   *   The target Url.
   */
  public function getTarget() {
    return $this->target;
  }

  /**
   * Setter for the target Url.
   *
   * @param \Drupal\Core\Url $target
   *   The target Url.
   */
  public function setTarget(Url $target) {
    $this->target = $target;
  }

}
