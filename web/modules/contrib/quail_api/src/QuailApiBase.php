<?php

namespace Drupal\quail_api;

use Drupal\Core\Database\Query\Condition;

/**
 * Class QuailApiBase.
 */
class QuailApiBase {

  /**
   * Loads the quail test data.
   *
   * @param array $conditions
   *   An array with the following possible keys:
   *   - id: The numeric id.
   *   - severity: A numeric value representing the severity (aka: display level)
   *     as defined by the quail library.
   *   - machine_name: The machine-friendly name for the test as defined by the
   *     quail library.
   * @param string|null $keyed
   *   (optional) A string matching one of the following: 'id', 'machine_name'.
   *   When this is NULL, the default behavior is to return the array exactly as
   *   it was returned by the database call.
   *   When this is a valid string, the key names of the returned array will use
   *   the specified key name.
   *
   * @return array
   *   An array of database results.
   */
  public static function load_tests($conditions = [], $keyed = NULL) {
    if (!is_array($conditions)) {
      return [];
    }

    $query = \Drupal::database()->select('quail_api_tests', 'qat');

    $query->fields('qat');
    $query->orderBy('qat.id', 'ASC');

    $and = NULL;

    if (isset($conditions['id']) && is_numeric($conditions['id'])) {
      $and = new Condition('AND');
      $and->condition('id', $conditions['id'], '=');
    }

    if (isset($conditions['severity']) && is_numeric($conditions['severity'])) {
      if (is_null($and)) $and = new Condition('AND');

      $and->condition('severity', $conditions['severity'], '=');
    }

    if (!empty($conditions['machine_name'])) {
      if (is_null($and)) $and = new Condition('AND');

      $and->condition('machine_name', $conditions['machine_name'], '=');
    }

    if (is_object($and)) $query->condition($and);

    if ($keyed === 'id' || $keyed === 'machine_name') {
      $results = [];

      try {
        $records = $query->execute();
      }
      catch (Exception $e) {
        return [];
      }
      catch (Error $e) {
        return [];
      }

      foreach ($records as $record) {
        if (!is_object($record)) continue;

        $results[$record->$keyed] = $record;
      }

      return $results;
    }

    try {
      return $query->execute()->fetchAll();
    }
    catch (Exception $e) {
    }
    catch (Error $e) {
    }

    return [];
  }

  /**
   * This stores the quail test information in the drupal database.
   *
   * The quail test data does not have a unique id associated with is so the
   * machine name must be used.
   * When the machine name is used, a unique id is associated.
   * If the machine name already exists then no write is performed.
   * If an update of the data is required then pass either the 'do_update'
   * vairable.
   *
   * @param array $test_data
   *   An array of test data with the following keys:
   *   - machine_name: This is the name of the test as defined by the quail
   *     library.
   *   - severity: This is the display level of the test as defined by the quail
   *     library.
   *   - human_name: This is a more title of the test as defiend by the quail
   *     library.
   *   - description: The detailed description of the test, which is defined by
   *     the quail library in the 'body' item.
   *   - id: (optional) The numeric id for existing data.
   *   - do_update: (optional) A boolean. When specified this tells forces an
   *     update if the machine name already exists.
   *
   * @return int|false
   *   The return states of either FALSE, SAVED_NEW, or SAVED_UPDATED.
   */
  public static function save_test($test_data) {
    if (!is_array($test_data)) {
      return FALSE;
    }

    $result = FALSE;
    $columns = ['machine_name', 'severity', 'human_name', 'description'];

    foreach ($columns as $key) {
      if (empty($test_data[$key])) {
        return FALSE;
      }
    }

    $results = self::load_tests(['machine_name' => $test_data['machine_name']], NULL);

    if (empty($results)) {
      $data = [];

      $data['machine_name'] = $test_data['machine_name'];
      $data['severity'] = $test_data['severity'];
      $data['human_name'] = $test_data['human_name'];
      $data['description'] = $test_data['description'];

      $result = \Drupal::database()->insert('quail_api_tests')
        ->fields($data)
        ->execute();
    }

    return $result;
  }

  /**
   * Resets all static caches provided by this module.
   */
  public static function reset_cache() {
    drupal_static_reset('quail_api_get_standards');
    drupal_static_reset('quail_api_get_severitys');
    drupal_static_reset('quail_api_get_validation_methods');

    \Drupal::cache('quail_api_standards')->invalidateAll();
    \Drupal::cache('quail_api_severity')->invalidateAll();
    \Drupal::cache('quail_api_validation_methods')->invalidateAll();
  }
}
