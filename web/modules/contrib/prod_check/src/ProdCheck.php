<?php

namespace Drupal\prod_check;

/**
 * Utility class for the prod check module
 */
class ProdCheck {

  /**
   * Our own definition of the core requirements states. These can be found in
   * includes/install.inc and are only available in hook_install(). That's why
   * we redefine them here (yes, it's double!). It's nicer than including the
   * install.inc file.
   */

  /**
   * Requirement severity -- Informational message only.
   */
  const REQUIREMENT_INFO = -1;

  /**
   * Requirement severity -- Requirement successfully met.
   */
  const REQUIREMENT_OK = 0;

  /**
   * Requirement severity -- Warning condition; proceed but flag warning.
   */
  const REQUIREMENT_WARNING = 1;

  /**
   * Requirement severity -- Error condition; abort installation.
   */
  const REQUIREMENT_ERROR = 2;

}
