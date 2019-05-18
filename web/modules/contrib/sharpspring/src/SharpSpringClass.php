<?php

namespace Drupal\sharpspring;

/**
 * SharpSpring functions
 * Class SharpSpringClass
 * @package Drupal\sharpspring
 */
class SharpSpringClass {

  /**
   * Helper to validate a SharpSpring Web Property ID's format w/ regex.
   *
   * @param string $id
   *   The value of the property ID.
   *
   * @return bool
   *   Verify that input matches format.
   */
  public static function validate_id($id) {
    // String begins with KOI-, and ends with 6+ word characters(a-z, 0-9 or _).
    return preg_match('/^KOI-\w{6,}\z/', $id);
  }

  /**
   * Helper to validate a SharpSpring base URI format w/ regex.
   *
   * @param string $input
   *   The value of the form's base URI.
   *
   * @return bool
   *   Verify that input matches format.
   */
  public static function validate_uri($input) {
    // String follows format:
    // https://app-XXXXXX.sharpspring.com/webforms/receivePostback/XXXXXXXX/.
    return preg_match('/^https:\/\/app-\w{5,}.sharpspring.com|.marketingautomation.services\/webforms\/receivePostback\/\w{6,}/i', $input);
  }

  /**
   * Helper to validate a SharpSpring domain format w/ regex.
   *
   * @param string $input
   *   The value of the API domain.
   *
   * @return bool
   *   Verify that input matches format.
   */
  public static function validate_domain($input) {
    // String begins with koi-, has a 5+ identifier, & ends with sharpspring.com.
    return preg_match('/^koi-\w{5,}.sharpspring.com|.marketingautomation.services\z/i', $input);
  }

  /**
   * Helper to validate a SharpSpring endpoint w/ regex.
   *
   * @param string $input
   *   The value of the form's endpoint value.
   *
   * @return bool
   *   Verify that input matches format.
   */
  public static function validate_endpoint($input) {
    // String follows format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.
    return preg_match('/^\w{8}-\w{4}-\w{4}-\w{4}-\w{12}\z/', $input);
  }

}
