<?php

namespace Drupal\smart_content\Event;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\smart_content\Variation\VariationInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a winning variation is selected.
 *
 * @package Drupal\smart_content\Event
 */
class DecisionEvent extends Event {

  /**
   * The event name.
   *
   * @var string
   */
  const EVENT_NAME = 'decision';

  /**
   * The AJAX response associated with this event.
   *
   * @var \Drupal\Core\Ajax\AjaxResponse
   */
  protected $response;

  /**
   * The winning variation.
   *
   * @var \Drupal\smart_content\Variation\VariationInterface
   */
  protected $variation;

  /**
   * WinnerSelectedEvent constructor.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   */
  public function __construct(AjaxResponse $response, VariationInterface $variation) {
    $this->response = $response;
    $this->variation = $variation;
  }

  /**
   * Gets the AJAX response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Gets the winning variation.
   *
   * @return \Drupal\smart_content\Variation\VariationInterface
   */
  public function getVariation() {
    return $this->variation;
  }

}
