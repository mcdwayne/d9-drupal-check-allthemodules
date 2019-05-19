<?php

namespace Drupal\yasm\Services;

/**
 * Defines yasm datatables interface.
 */
interface DatatablesInterface {

  /**
   * Get datatables current version.
   */
  public function getVersion();

  /**
   * Get datatables locale if exists.
   */
  public function getLocale();

}
