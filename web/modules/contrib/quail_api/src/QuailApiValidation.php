<?php

namespace Drupal\quail_api;

use Drupal\quail_api\QuailApiSettings;

/**
 * Class QuailApiValidation.
 */
class QuailApiValidation {

  /**
   * Perform accessibility validation of the given HTML snippet.
   *
   * @param string $html
   *   A string of HTML to validate.
   * @param array $standard
   *   An array containing a single standard to use as returned by:
   *   QuailApiSettings::get_standards().
   * @param string|null $severity
   *   (optional) An array of booleans representing the quail test display levels
   *
   * @return array
   *   An array of validation results with the following array keys.
   */
  public static function validate($html, $standard, $severity = NULL) {
    $results = ['report' => NULL, 'total' => 0];

    // the libraries module is currently incomplete and drupal core does not
    // provide remote PHP integration. Manually include the API.
    $include_path = drupal_get_path('module', 'quail_api');

    if (!file_exists($include_path . '/vendor/quail/quail.php')) {
      $results['report'] = FALSE;
      return $results;
    }

    include_once($include_path . '/vendor/quail/quail.php');
    include_once($include_path . "/includes/quail_api_guidelines.inc");
    include_once($include_path . "/includes/quail_api_reporters.inc");

    // quail-lib fails when the markup is empty.
    // empty markup is valid, so bypass the validator and return success.
    if (empty($html)) {
      $results['report'] = [];
      return $results;
    }

    if (!is_array($severity)) {
      $severity = QuailApiSettings::get_default_severity();
    }

    $quail = new \quail($html, $standard['guideline'], 'string', $standard['reporter']);

    $quail->runCheck(
      [
        'ac_module_guideline' => $standard['guideline'],
        'severity' => $severity,
      ]
    );

    $results = $quail->getReport();
    $quail->cleanup();

    if (!isset($results['report']) || !is_array($results['report'])) {
      $results['report'] = FALSE;
    }

    return $results;
  }
}
