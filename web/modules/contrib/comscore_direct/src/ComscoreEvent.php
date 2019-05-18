<?php

namespace Drupal\comscore_direct;

use Symfony\Component\EventDispatcher\Event;

class ComscoreEvent extends Event {

  /**
   * The comscore information.
   *
   * @var \Drupal\comscore_direct\ComscoreInformation
   */
  protected $comscoreInformation;

  /**
   * Creates a new ComscoreEvent instance.
   *
   * @param \Drupal\comscore_direct\ComscoreInformation $comscoreInformation
   *   The comscore information.
   */
  public function __construct(ComscoreInformation $comscoreInformation) {
    $this->comscoreInformation = $comscoreInformation;
  }

  /**
   * Gets the comscore information.
   *
   * @return \Drupal\comscore_direct\ComscoreInformation
   */
  public function getComscoreInformation() {
    return $this->comscoreInformation;
  }

  /**
   * Sets the comscore information.
   *
   * @param \Drupal\comscore_direct\ComscoreInformation $comscore_information
   *   The comscore information.
   *
   * @return $this;
   */
  public function setComscoreInformation(ComscoreInformation $comscore_information) {
    $this->comscoreInformation = $comscore_information;
    return $this;
  }

}
