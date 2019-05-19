<?php

namespace Drupal\tealium\Data;

/**
 * Class Interface for binding Tealium utag_data to a jQuery selector event.
 */
interface TealiumJqueryEventBindingInterface {

  /**
   * Set a jQuery selector for an element to bind the Data to.
   */
  public function setJquerySelector($selector);

  /**
   * Gets jQuery selector value.
   */
  public function getJquerySelector();

  /**
   * Sets domEvent value.
   */
  public function setDomEvent($event_name);

  /**
   * Gets domEvent value.
   */
  public function getDomEvent();

  /**
   * Set the Tealium tracking type to use [link|view].
   */
  public function setTrackType($type);

  /**
   * Gets track type value.
   */
  public function getTrackType();

  /**
   * Sets data for tealium javascript.
   */
  public function setTealiumData(TealiumUtagData $dataTealiumSourcevalues);

  /**
   * Gets all data for tealium javascript.
   */
  public function getTealiumData();

}
