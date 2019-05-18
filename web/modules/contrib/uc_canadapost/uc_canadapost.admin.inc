<?php

/**
 * @file
 * Canada Post module administration menu callbacks.
 *
 * @author Tim Rohaly.    <http://drupal.org/user/202830>
 */


  /**
   * Utility routine to construct options for date format.
   *
   * @return
   *   Array of today's date in various formats.
   */
  function _uc_canadapost_get_date_options() {
    $date_options = array(
      'm/d/Y', 'd/m/Y', 'Y/m/d',
      'D, M j, Y', 'D, M j', 'F j, Y',
      'l, j F', 'l, F j',
      'l, j F, Y', 'l, Y, F j', 'l, F j, Y'
    );
    $date_options_nice = array('' => t("Don't display estimate"));

    foreach ($date_options as $datefmt) {
      $date_options_nice[$datefmt] = t("Estimated delivery") . ': ' . date($datefmt);
    }
    return $date_options_nice;
  }

  /**
   * Utility function to determine province based on postal code.
   *
   * Not currently used by this module.
   *
   * FSA (= Forward Sortation Area) code is first three characters of
   * postal code.  The first letter indicates the postal district, which
   * has an <em>almost</em> 1 to 1 correspondence with province.  The
   * exceptions are that NU and NT are in the same postal district,
   * and ON and QC both have multiple districts.
   *
   * @param $postalcode
   *   6-character postal code.
   *
   * @return
   *   2-character province name abbreviation.
   */
  function _uc_canadapost_lookup_province($postalcode) {

    $fsa = drupal_strtoupper(drupal_substr($postalcode, 0, 1));
    $province = array(
      'A' => 'NL',
      'B' => 'NS',
      'C' => 'PE',
      'E' => 'NB',
      'G' => 'QC',
      'H' => 'QC',
      'J' => 'QC',
      'K' => 'ON',
      'L' => 'ON',
      'M' => 'ON',
      'N' => 'ON',
      'P' => 'ON',
      'R' => 'MB',
      'S' => 'SK',
      'T' => 'AB',
      'V' => 'BC',
      'X' => 'NT',  // X is also NU, but we can only return one value here...
      'Y' => 'YT',
    );
    return $province[$fsa];
  }
