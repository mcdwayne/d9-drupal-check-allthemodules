<?php

namespace Drupal\google_geochart;

/**
 * Interface GeochartDataAlterServiceInterface.
 */
interface GeochartDataAlterServiceInterface {

  /**
   * Get the default saved data.
   *
   * @return array $data
   *   The array of saved data.
   */
  public function getGeochartData();

  /**
   * Set the default data.
   *
   * @param array $data
   *   The array.
   */
  public function setGeochartData($data);

}
