<?php

namespace Drupal\tealium;

use Drupal\tealium\Data\TealiumJqueryEventBindingInterface;

/**
 * Tealium data interface.
 */
interface TealiumDataServiceInterface {

  /**
   * Set a variable in the Tealium Universal Data Object to be sent for a page.
   *
   * @param string $name
   *   Name of a Tealium Universal Data Object property to set.
   *   This name will map to a Data Source in your Tealium Management console.
   * @param mixed $value
   *   Value to assign.
   */
  public function addData($name, $value = NULL);

  /**
   * Get variables from the Tealium Universal Data Object to be sent for a page.
   *
   * @return \Drupal\tealium\Data\TealiumUtagDataInterface
   *   Tealium variables.
   */
  public function getData();

  /**
   * Set a variable to be sent to Tealium as a link tracking event for the page.
   *
   * @param string $name
   *   Name of a Tealium Universal Data Object property to set.
   *   This name will map to a Data Source in your Tealium Management console.
   * @param mixed $value
   *   Value to assign.
   */
  public function addLinkData($name, $value = NULL);

  /**
   * Get variables from the Tealium as a link tracking event for the page.
   *
   * @return \Drupal\tealium\Data\TealiumUtagDataInterface
   *   Tealium variables.
   */
  public function getLinkData();

  /**
   * Set a variable to be sent to Tealium as a view tracking event for the page.
   *
   * @param string $name
   *   Name of a Tealium Universal Data Object property to set.
   *   This name will map to a Data Source in your Tealium Management console.
   * @param mixed $value
   *   Value to assign.
   */
  public function addViewData($name, $value = NULL);

  /**
   * Get variables from the Tealium as a view tracking event for the page.
   *
   * @return \Drupal\tealium\Data\TealiumUtagDataInterface
   *   Tealium variables.
   */
  public function getViewData();

  /**
   * Add binding to an DOM element to send data to Tealium when an event fires.
   *
   * @param \Drupal\tealium\Data\TealiumJqueryEventBindingInterface $bind_utag_data_event
   *   The object describing the utag_data element event binding.
   */
  public function addBindData(TealiumJqueryEventBindingInterface $bind_utag_data_event);

  /**
   * Get bindings to DOM elements to send data to Tealium when an event fires.
   *
   * @return \Drupal\tealium\Data\TealiumJqueryEventBindingInterface[]
   *   Bound data.
   */
  public function getBindData();

}
