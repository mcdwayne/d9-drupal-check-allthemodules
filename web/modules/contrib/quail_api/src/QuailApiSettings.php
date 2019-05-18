<?php

namespace Drupal\quail_api;

/**
 * Class QuailApiSettings.
 */
class QuailApiSettings {

  /**
   * Returns an array of standards that are supported.
   *
   * @param string|null $standard
   *   (optional) Providing a valid standard name will cause the return value to
   *   only contain the standard that matches this string.
   * @param string $target
   *   (optional) Target allows for selecting standards based on some category or
   *   purpose.
   *   The target, in general, represents the scope in which the standards will
   *   be applied.
   *   The following targets are directly supported: 'snippet', 'page'.
   *
   * @return array
   *   An array of standards that are supported by this module or extending
   *   modules.
   *   The array keys are the machine names for each standard.
   */
  public static function get_standards($standard = NULL, $target = 'snippet') {
    $standards = &drupal_static('quail_api_' . __FUNCTION__, NULL);

    if (!isset($standards)) {
      if ($cache = \Drupal::cache()->get('quail_api_standards')) {
        $standards = $cache->data;
      }
      else {
        \Drupal::moduleHandler()->alter('quail_api_get_standards', $standard, $other_arguments);
      }
    }

    if (isset($standards)) {
      if (!is_null($standard)) {
        if (isset($standards[$standard])) {
          return $standards[$standard];
        }

        return [];
      }

      return $standards;
    }

    $standards = [];

    if ($target == 'snippet' || $target == 'page') {
      $reporter = 'quail_api';
      $module = 'quail_api';

      $standards['all'] = [
        'human_name' => t("All Tests"),
        'module' => $module,
        'description' => t(
          "Validate using all known tests. This is more efficient than validating against each individual standard because identical tests are not run multiple times."
        ),
        'guideline' => ($target == 'snippet') ? 'quail_api_all' : 'all',
        'reporter' => $reporter,
        'target' => $target,
      ];

      $standards['section_508'] = [
        'human_name' => t("Section 508"),
        'module' => $module,
        'description' => t(
          "Validate using the <a href='@section_508'>Section 508</a> standard.",
          ['@section_508' => 'http://www.section508.gov/index.cfm?fuseAction=stdsdoc#Web']
        ),
        'guideline' => ($target == 'snippet') ? 'quail_api_section508' : 'section508',
        'reporter' => $reporter,
        'target' => $target,
      ];

      $standards['wcag_1_0_a'] = [
        'human_name' => t("WCAG 1.0 a"),
        'module' => $module,
        'description' => t(
          "Validate using the <a href='@wcag_1_0'>WCAG 1.0</a> (<a href='@wcag_1_0_a'>A</a>) standard.",
          ['@wcag_1_0' => 'http://www.w3.org/TR/WCAG10/', '@wcag_1_0_a' => 'http://www.w3.org/WAI/WCAG1A-Conformance']
        ),
        'guideline' => ($target == 'snippet') ? 'quail_api_wcag1a' : 'wcag1a',
        'reporter' => $reporter,
        'target' => $target,
      ];

      $standards['wcag_1_0_aa'] = [
        'human_name' => t("WCAG 1.0 aa"),
        'module' => $module,
        'description' => t(
          "Validate using the <a href='@wcag_1_0'>WCAG 1.0</a> (<a href='@wcag_1_0_aa'>AA</a>) standard.",
          ['@wcag_1_0' => 'http://www.w3.org/TR/WCAG10/', '@wcag_1_0_a' => 'http://www.w3.org/WAI/WCAG1AA-Conformance']
        ),
        'guideline' => ($target == 'snippet') ? 'quail_api_wcag1aa' : 'wcag1aa',
        'reporter' => $reporter,
        'target' => $target,
      ];

      $standards['wcag_1_0_aaa'] = [
        'human_name' => t("WCAG 1.0 aaa"),
        'module' => $module,
        'description' => t(
          "Validate using the <a href='@wcag_1_0'>WCAG 1.0</a> (<a href='@wcag_1_0_aaa'>AAA</a>) standard.",
          ['@wcag_1_0' => 'http://www.w3.org/TR/WCAG10/', '@wcag_1_0_a' => 'http://www.w3.org/WAI/WCAG1AAA-Conformance']
        ),
        'guideline' => ($target == 'snippet') ? 'quail_api_wcag1aaa' : 'wcag1aaa',
        'reporter' => $reporter,
        'target' => $target,
      ];

      $standards['wcag_2_0_a'] = [
        'human_name' => t("WCAG 2.0 a"),
        'module' => $module,
        'description' => t(
          "Validate using the <a href='@wcag_2_0'>WCAG 2.0</a> (<a href='@wcag_2_0_a'>A</a>) standard.",
          ['@wcag_2_0' => 'http://www.w3.org/TR/WCAG20/', '@wcag_1_0_a' => 'http://www.w3.org/WAI/WCAG2A-Conformance']
        ),
        'guideline' => ($target == 'snippet') ? 'quail_api_wcag2a' : 'wcag2a',
        'reporter' => $reporter,
        'target' => $target,
      ];

      $standards['wcag_2_0_aa'] = [
        'human_name' => t("WCAG 2.0 aa"),
        'module' => $module,
        'description' => t(
          "Validate using the <a href='@wcag_2_0'>WCAG 2.0</a> (<a href='@wcag_2_0_aa'>AA</a>) standard.",
          ['@wcag_2_0' => 'http://www.w3.org/TR/WCAG20/', '@wcag_1_0_a' => 'http://www.w3.org/WAI/WCAG2AA-Conformance']
        ),
        'guideline' => ($target == 'snippet') ? 'quail_api_wcag2aa' : 'wcag2aa',
        'reporter' => $reporter,
        'target' => $target,
      ];

      $standards['wcag_2_0_aaa'] = [
        'human_name' => t("WCAG 2.0 aaa"),
        'module' => $module,
        'description' => t(
          "Validate using the <a href='@wcag_2_0'>WCAG 2.0</a> (<a href='@wcag_2_0_aaa'>AAA</a>) standard.",
          ['@wcag_2_0' => 'http://www.w3.org/TR/WCAG20/', '@wcag_1_0_a' => 'http://www.w3.org/WAI/WCAG2AAA-Conformance']
        ),
        'guideline' => ($target == 'snippet') ? 'quail_api_wcag2aaa' : 'wcag2aaa',
        'reporter' => $reporter,
        'target' => $target,
      ];
    }

    $other_arguments = [];
    $other_arguments['target'] = $target;

    \Drupal::cache()->set('quail_api_standards', $standards);

    if (!is_null($standard)) {
      if (isset($standards[$standard])) {
        return $standards[$standard];
      }

      return [];
    }

    return $standards;
  }

  /**
   * Returns a list of standards.
   *
   * This is only a list of the machine_name and human_name of the select lists.
   * Use this for populating select lists, radio buttons, and check boxes.
   *
   * @param array|null $standards
   *   Providing a valid array of standards as returned by
   *   QuailApiSettings::get_standards() and it will be properly converted into a
   *   standards list.
   * @param string $target
   *   (optional) Providing a target allows for limiting standards by some
   *   category.
   *   The target, in general, represents the scope in which the standards will
   *   be applied.
   *   The following targets are directly supported: 'snippet', 'page'.
   *
   * @return array
   *  An array of standards that are supported by this module or extending
   *  modules.
   *  The array keys are the standard machine name and the array value is the
   *  human name.
   *
   * @see QuailApiSettings::get_standards()
   */
  public static function get_standards_list($standards = NULL, $target = 'snippet') {
    if (is_null($standards)) {
      $standards = static::get_standards(NULL, $target);
    }

    $standards_list = [];

    foreach ($standards as $machine_name => $value) {
      if (!isset($value['target']) || $value['target'] != $target) {
        continue;
      }

      if (isset($value['human_name'])) {
        $standards_list[$machine_name] = $value['human_name'];
      }
    }

    return $standards_list;
  }

  /**
   * Returns an array of severity levels that are supported.
   *
   * @param string|null $severity
   *   (optional) A number representing the display level.
   *   When defined, the return value to only contain the display level that
   *   matches this string.
   *   When undefined, all display levels will be loaded into the severitys
   *   array.
   *
   * @return array
   *   An array of display levels that are supported by this module or extending
   *   modules.
   *   The array keys are the machine names for each display level.
   */
  public static function get_severity($severity = NULL) {
    $severitys = &drupal_static('quail_api_' . __FUNCTION__, NULL);

    if (!isset($severitys)) {
      if ($cache = \Drupal::cache()->get('quail_api_severity')) {
        $severitys = $cache->data;
      }
      else {
        \Drupal::moduleHandler()->alter('quail_api_severity', $severitys, $severity);
      }
    }

    if (isset($severitys)) {
      if (!is_null($severity)) {
        if (isset($severitys[$severity])) {
          return $severitys[$severity];
        }

        return [];
      }

      return $severitys;
    }

    $severitys = [];

    $severitys[1] = [
      'machine_name' => 'quail_test_major',
      'human_name' => t("Major Problems"),
      'module' => 'quail_api',
      'description' => t(
        "Major problems represent critical failures in accessibility compliance."
      ),
      'id' => 1, // QUAIL_TEST_SEVERE
      'default' => TRUE,
    ];

    $severitys[2] = [
      'machine_name' => 'quail_test_minor',
      'human_name' => t("Minor Problems"),
      'module' => 'quail_api',
      'description' => t(
        "Minor problems represent simple failures in accessibility compliance."
      ),
      'id' => 2, // QUAIL_TEST_MODERATE
      'default' => TRUE,
    ];

    $severitys[3] = [
      'machine_name' => 'quail_test_suggestion',
      'human_name' => t("Suggestions"),
      'module' => 'quail_api',
      'description' => t(
        "Suggestions provide notes and tips on how to improve accessibility compliance."
      ),
      'id' => 3, // QUAIL_TEST_SUGGESTION
      'default' => TRUE,
    ];

    \Drupal::cache()->set('quail_api_severity', $severitys);

    if (!is_null($severity)) {
      if (isset($severitys[$severity])) {
        return $severitys[$severity];
      }

      return [];
    }

    return $severitys;
  }

  /**
   * Returns a list of display levels that are supported.
   *
   * This is only a list of the machine_name and human_name of the select lists.
   * Use this for populating select lists, radio buttons, and check boxes.
   *
   * @param array|null $severitys
   *   (optional) Providing a valid array of display lists as returned by
   *   QuailApiSettings::get_severity() and it will be properly converted into a
   *   display levels list.
   * @return array
   *   An array of display levels that are supported by this module or extending
   *   modules.
   *   The array keys are the display levels machine name and the array value is
   *   the human name.
   *
   * @see QuailApiSettings::get_severity()
   */
  public static function get_severity_list($severitys = NULL) {
    if (is_null($severitys)) {
      $severitys = QuailApiSettings::get_severity();
    }

    $severitys_list = [];

    foreach ($severitys as $machine_name => $value) {
      if (isset($value['human_name'])) {
        $severitys_list[$machine_name] = $value['human_name'];
      }
    }

    return $severitys_list;
  }

  /**
   * Returns an array of quail test display levels, each initialized to TRUE.
   *
   * @param array|null $standards
   *   (optional) Providing a valid array of standards as returned by
   *   QuailApiSettings::get_severity() and it will be properly converted into a
   *   standards list.
   *
   * @return array
   *   An array of quail test display levels, each initialized to TRUE.
   */
  public static function get_default_severity($severitys = NULL) {
    if (is_null($severitys)) {
      $severitys = static::get_severity();
    }

    $severity = [];

    foreach ($severitys as $id => $value) {
      if (isset($value['default'])) {
        $severity[$value['id']] = $value['default'];
      }
    }

    return $severity;
  }

  /**
   * Returns an array of validation methods that are supported.
   *
   * A validation method is how the validation process is to be performed.
   * Examples are:
   * - performing the validation everytime a page is viewed
   * - performing the validation only when a validate button is checked
   *
   * This is mostly information.
   * Extending modules are expected to provide the functionality that utilizes
   * this data.
   *
   * @param string|null $validation_method
   *   (optional) A machine name representing of the validation method.
   *   When defined, the return value to only contain the validation method that
   *   matches the given id.
   *   When undefined, all validation methods will be loaded into the
   *   validation_method array.
   *
   * @return array
   *   An array of validation methods that are supported by this module or
   *   extending modules.
   *   The array keys are the machine names for each display level.
   */
  public static function get_validation_methods($validation_method = NULL) {
    $validation_methods = &drupal_static('quail_api_' . __FUNCTION__, NULL);

    if (!isset($validation_methods)) {
      if ($cache = \Drupal::cache()->get('quail_api_validation_methods')) {
        $validation_methods = $cache->data;
      }
      else {
        \Drupal::moduleHandler()->alter('quail_api_validation_methods', $validation_methods, $validation_method);
      }
    }

    if (isset($validation_methods)) {
      if (!is_null($validation_method)) {
        if (isset($validation_methods[$validation_method])) {
          return $validation_methods[$validation_method];
        }

        return [];
      }

      return $validation_methods;
    }

    $validation_methods = [];

    $validation_methods['quail_api_method_immediate'] = [
      'human_name' => t("Immediately Validate"),
      'module' => 'quail_api',
      'description' => t(
        "Always perform the validation. Validation results are never saved."
      ),
      'database' => FALSE,
      'automatic' => TRUE,
    ];

    $validation_methods['quail_api_method_manual'] = [
      'human_name' => t("Manually Validate"),
      'module' => 'quail_api',
      'description' => t(
        "Perform the validation only when requested. Validation results are never saved."
      ),
      'database' => FALSE,
      'automatic' => FALSE,
    ];

    $validation_methods['quail_api_method_immediate_database'] = [
      'human_name' => t("Immediately Validate & Save"),
      'module' => 'quail_api',
      'description' => t(
        "Always perform the validation. Validation results are stored in the database."
      ),
      'database' => TRUE,
      'automatic' => TRUE,
    ];

    $validation_methods['quail_api_method_manual_database'] = [
      'human_name' => t("Manually Validate & Save"),
      'module' => 'quail_api',
      'description' => t(
        "Perform the validation only when requested. Validation results are stored in the database."
      ),
      'database' => TRUE,
      'automatic' => FALSE,
    ];

    \Drupal::cache()->set('quail_api_validation_methods', $validation_methods);

    if (!is_null($validation_method)) {
      if (isset($validation_methods[$validation_method])) {
        return $validation_methods[$validation_method];
      }

      return [];
    }

    return $validation_methods;
  }

  /**
   * Returns a list of validation methods.
   *
   * This is only a list of the machine_name and human_name of the select lists.
   * Use this for populating select lists, radio buttons, and check boxes.
   *
   * @param array|null $validation_methods
   *   (optional) Providing a valid array of validation methods as returned by
   *   QuailApiSettings::get_validation_methods() and it will be properly converted into
   *   a validation methods list.
   *
   * @return array
   *   An array of validation methods that are supported by this module or
   *   extending modules.
   *   The array keys are the validation methods machine name and the array value
   *   is the human name.
   */
  public static function get_validation_methods_list($validation_methods = NULL) {
    if (is_null($validation_methods)) {
      $validation_methods = static::get_validation_methods();
    }

    $validation_methods_list = [];

    foreach ($validation_methods as $machine_name => $value) {
      if (isset($value['human_name'])) {
        $validation_methods_list[$machine_name] = $value['human_name'];
      }
    }

    return $validation_methods_list;
  }
}
