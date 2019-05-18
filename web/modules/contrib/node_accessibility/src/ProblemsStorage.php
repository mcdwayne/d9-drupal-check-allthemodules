<?php

namespace Drupal\node_accessibility;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Transaction;
use Drupal\Core\Session\AccountInterface;
use Drupal\node_accessibility\TypeSettingsStorage;
use Drupal\quail_api\QuailApiBase;
use Drupal\quail_api\QuailApiSettings;

/**
 * Class DatabaseStorage.
 */
class ProblemsStorage {

  /**
   * Saves the node report data to the database.
   *
   * @param int $nid
   *   The node id of the node associated with the given reports.
   * @param int $vid
   *   The node revision id of the node associated with the given reports.
   * @param array $reports
   *   The reports array as returned by the quail library quail api reporter.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public static function save_node_problems($nid, $vid, $reports) {
    if (!is_array($reports)) {
      return FALSE;
    }

    $tests_known = (array) QuailApiBase::load_tests([], 'machine_name');
    $problems = [];

    foreach ($reports as $severity => $severity_results) {
      if ($severity == 'total') {
        continue;
      }

      foreach ($severity_results as $test_name => $test_results) {
        if ($test_name == 'total') continue;

        if (!(array_key_exists($test_name, $tests_known))) {
          if (empty($test_results['body']['title']) || empty($test_results['body']['description'])) {
            continue;
          }

          $test_data = [];
          $test_data['machine_name'] = $test_name;
          $test_data['severity'] = $severity;
          $test_data['human_name'] = $test_results['body']['title'];
          $test_data['description'] = $test_results['body']['description'];

          $results = QuailApiBase::save_test($test_data);

          if ($results === FALSE) {
            \Drupal::logger('node_accessibility')->error("Failed to insert @machine_name into quail api tests database table.", ['@machine_name' => $test_name]);
            continue;
          }

          // The row must be loaded from the database so that the id can be retrieved
          $loaded_test = QuailApiBase::load_tests(['machine_name' => $test_name], NULL);

          if (!isset($loaded_test['0']) || !is_object($loaded_test['0'])) {
            \Drupal::logger('node_accessibility')->error("Failed to insert @machine_name problems into node accessibility tests database table because is not a valid object.", ['@machine_name' => $test_name]);
            continue;
          }

          $tests_known[$test_name] = $loaded_test['0'];
        }

        foreach ($test_results['problems'] as $problem_name => $problem) {
          if (empty($problem['line']) || empty($problem['element'])) {
            continue;
          }

          $problem_data = [];
          $problem_data['nid'] = $nid;
          $problem_data['vid'] = $vid;
          $problem_data['test_id'] = $tests_known[$test_name]->id;
          $problem_data['test_severity'] = $tests_known[$test_name]->severity;
          $problem_data['line'] = $problem['line'];
          $problem_data['element'] = $problem['element'];

          $problems[] = $problem_data;
        }
      }
    }

    if (!empty($problems)) {
      $results = self::replace_problem($nid, $vid, $problems);
      if ($results === FALSE) {
        \Drupal::logger('node_accessibility')->error("Failed to insert @machine_name problems into node accessibility problems database table.", ['@machine_name' => $test_name]);
      }
    }

    $account = \Drupal::currentUser();
    $results = self::replace_problem_stats((int) $account->id(), (int) $nid, (int) $vid, (int) microtime(TRUE));
    if ($results === FALSE) {
      \Drupal::logger('node_accessibility')->error("Failed to update the accessibility problem statistics database table for node @nid with reveision @vid.", ['@nid' => $nid, '@vid' => $vid]);
    }
  }

  /**
   * Deletes the node report data from the database.
   *
   * This is primarly used to remove data for a node that no longer has any
   * validation failures.
   *
   * @param int $nid
   *   The node id of the node associated with the given reports.
   * @param int|null $vid
   *   (optional) The node revision id of the node associated with the given
   *   reports.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public static function delete_node_problems($nid, $vid = NULL) {
    if (!is_numeric($nid)) {
      return FALSE;
    }

    if (!is_null($vid) && !is_numeric($vid)) {
      return FALSE;
    }

    $query = \Drupal::database()->delete('node_accessibility_problems');
    $query->condition('nid', $nid);

    if (!is_null($vid)) {
      $query->condition('vid', $vid);
    }

    try {
      return $query->execute();
    }
    catch (Exception $e) {
      \Drupal::logger('node_accessibility')->error("Failed to delete node problems with nid=@nid and vid=@vid.", ['@nid' => $nid, '@vid' => $vid]);
    }
    catch (Error $e) {
      \Drupal::logger('node_accessibility')->error("Failed to delete node problems with nid=@nid and vid=@vid.", ['@nid' => $nid, '@vid' => $vid]);
    }

    return FALSE;
  }

  /**
   * Loads the nodes problem data.
   *
   * @param array $conditions
   *   (optional) An array with the following possible keys:
   *   - 'id' The unique id representing a specific problem.
   *   - 'nid' the node id.
   *   - 'vid' the node revision id.
   *   - 'test_id' a numeric value representing the id of the test the problem
   *     is associated with.
   *   - 'test_severity' a numeric value representing the severity of the
   *     problem.
   *   - 'line' a numeric value representing the line number in which a problem
   *     applies to.
   *   - 'live_only' a boolean that specifies whether or not to restrict loading
   *     of problems to live content (active revision). (defaults to FALSE)
   *   - 'unlive_only' a boolean that specifies whether or not to restrict
   *     loading of problems to non-live content (active revision). (defaults to
   *     FALSE)
   *   - 'published_only' a boolean that specifies whether or not to restrict
   *     loading of problems to published content (active revision). (defaults
   *     to FALSE)
   *   - 'unpublished_only' a boolean that specifies whether or not to restrict
   *     loading of problems to unpublished content (active revision). (defaults
   *     to FALSE)
   *   - 'sort_by' the name of a column to sort by.
   *   - 'sort_order' the order in which to sort by ('asc' or 'desc').
   *   - 'node_columns' a boolean that specifies whether or not to load the node
   *     column fields in addition to the node accessibility problems fields.
   *     (defaults to FALSE)
   * @param string|null $keyed
   *   (optional) A string matching one of the following: 'id'.
   *   When this is NULL, the default behavior is to return the array exactly as
   *   it was returned by the database call.
   *   When this is a valid string, the key names of the returned array will use
   *   the specified key name.
   *
   * @return array
   *   An array of database results.
   */
  public static function load_problems($conditions = [], $keyed = NULL) {
    if (!is_array($conditions)) {
      return [];
    }

    $query = \Drupal::database()->select('node_accessibility_problems', 'nap');
    $query->fields('nap');

    $sort_by = 'nap.nid';
    $sort_order = 'ASC';

    if (isset($conditions['sort_order'])) {
      switch ($conditions['sort_order']) {
        case 'ASC':
        case 'DESC':
          $sort_order = $conditions['sort_order'];
          break;
      }
    }

    if (isset($conditions['sort_by']) && is_string($conditions['sort_by'])) {
      switch ($conditions['sort_by']) {
        case 'id':
        case 'nid':
        case 'vid':
        case 'test_id':
        case 'test_severity':
        case 'line':
        case 'element':
          $sort_by = 'nap.' . $conditions['sort_by'];
          break;

        default:
          $sort_by = $conditions['sort_by'];
          break;
      }
    }

    $query->orderBy($sort_by, $sort_order);

    $and = NULL;
    $joined = FALSE;

    if (isset($conditions['live_only']) && is_bool($conditions['live_only'])) {
      $query->innerjoin('node', 'n', 'nap.vid = n.vid');
      $joined = TRUE;
    }
    else if (isset($conditions['unlive_only']) && is_bool($conditions['unlive_only'])) {
      $query->innerjoin('node', 'n', 'nap.nid = n.nid');
      $joined = TRUE;
      $and = new Condition('AND');

      $and->where('NOT nap.vid = n.vid');
    }

    if (isset($conditions['published_only']) && is_bool($conditions['published_only'])) {
      if (!$joined) {
        $query->innerjoin('node', 'n', 'nap.nid = n.nid');
        $joined = TRUE;
      }

      if (is_null($and)) $and = new Condition('AND');

      $and->condition('n.status', 1, '=');
    }
    else if (isset($conditions['unpublished_only']) && is_bool($conditions['unpublished_only'])) {
      if (!$joined) {
        $query->innerjoin('node', 'n', 'nap.nid = n.nid');
        $joined = TRUE;
      }

      if (is_null($and)) $and = new Condition('AND');

      $and->condition('n.status', 0, '=');
    }

    if (isset($conditions['id']) && is_numeric($conditions['id'])) {
      if (is_null($and)) $and = new Condition('AND');

      $and->condition('nap.id', $conditions['id'], '=');
    }

    if (isset($conditions['nid']) && is_numeric($conditions['nid'])) {
      if (is_null($and)) $and = new Condition('AND');

      $and->condition('nap.nid', $conditions['nid'], '=');
    }

    if (isset($conditions['vid']) && is_numeric($conditions['vid'])) {
      if (is_null($and)) $and = new Condition('AND');

      $and->condition('nap.vid', $conditions['vid'], '=');
    }

    if (isset($conditions['test_id']) && is_numeric($conditions['test_id'])) {
      if (is_null($and)) $and = new Condition('AND');

      $and->condition('nap.test_id', $conditions['test_id'], '=');
    }

    if (isset($conditions['test_severity']) && is_numeric($conditions['test_severity'])) {
      if (is_null($and)) $and = new Condition('AND');

      $and->condition('nap.test_severity', $conditions['test_severity'], '=');
    }

    if (!empty($conditions['line'])) {
      if (is_null($and)) $and = new Condition('AND');

      $and->condition('nap.line', $conditions['line'], '=');
    }

    if (is_object($and)) $query->condition($and);

    if (isset($conditions['node_columns']) && is_bool($conditions['node_columns'])) {
      if (!$joined) {
        $query->innerjoin('node', 'n', 'nap.nid = n.nid');
        $joined = TRUE;
      }

      $query->fields('n');
    }

    if ($keyed === 'id') {
      $results = [];

      try {
        $records = $query->execute();
      }
      catch (Exception $e) {
        \Drupal::logger('node_accessibility')->error("Failed to load problems.");
        return [];
      }
      catch (Error $e) {
        \Drupal::logger('node_accessibility')->error("Failed to load problems.");
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
      \Drupal::logger('node_accessibility')->error("Failed to load problems.");
    }
    catch (Error $e) {
      \Drupal::logger('node_accessibility')->error("Failed to load problems.");
    }

    return [];
  }

  /**
   * Load count of problems by severity.
   *
   * @param int $severity_id
   *   The severity id code
   * @param int|null $nid
   *   (optional) The numeric node id to filter by.
   * @param int|null $vid
   *   (optional) The numeric node revision id to filter by.
   *
   * @return int|bool
   *   The total of problems for the severity is returned on success.
   *   FALSE is returned otherwise.
   *
   * @see QuailApiSettings::get_severity()
   * @see QuailApiSettings::get_severity_list()
   */
  public static function load_problem_severity_count($severity_id, $nid = NULL, $vid = NULL) {
    if (!is_numeric($severity_id)) {
      return FALSE;
    }

    if (!is_null($nid) && !is_numeric($nid)) {
      return FALSE;
    }

    if (!is_null($vid) && !is_numeric($vid)) {
      return FALSE;
    }

    try {
      $query = \Drupal::database()->select('node_accessibility_problems', 'nap');
      $query->condition('nap.test_severity', $severity_id);

      if (is_numeric($nid)) {
        $query->condition('nap.nid', $nid);
      }

      if (is_numeric($vid)) {
        $query->condition('nap.vid', $vid);
      }

      $result = $query->countQuery()->execute()->fetchField();
      if (is_numeric($result)) {
        return (int) $result;
      }
    }
    catch (Exception $e) {
      \Drupal::logger('node_accessibility')->error("Failed to load problems.");
    }
    catch (Error $e) {
      \Drupal::logger('node_accessibility')->error("Failed to load problems.");
    }

    return FALSE;
  }

  /**
   * This stores validation problems for a single node to the database.
   *
   * This will delete all pre-existing problems for the given node.
   *
   * @param int $nid
   *   The node id.
   * @param int $vid
   *   The node revision id.
   * @param array $problems
   *   An array of arrays of test data containing the test problems
   *   - each nested array should containt the following keys:
   *     - nid: The node id of the node.
   *     - vid: The node revision id of the node.
   *     - test_id: The id of the problem.
   *     - test_severity: The severity of the problem.
   *     - line: The line number of the problem.
   *     - element: The html markup of the problem.
   *
   * @return int|false
   *   The return states of either FALSE, SAVED_NEW, or SAVED_UPDATED.
   */
  public static function replace_problem($nid, $vid, $problems) {
    if (!is_numeric($nid)) {
      return FALSE;
    }

    if (!is_numeric($vid)) {
      return FALSE;
    }

    if (!is_array($problems)) {
      return FALSE;
    }

    $result = FALSE;

    $transaction = \Drupal::database()->startTransaction();
    try {
      $query = \Drupal::database()->delete('node_accessibility_problems');
      $query->condition('nid', $nid);
      $query->condition('vid', $vid);
      $query->execute();

      foreach ($problems as $problem) {
        $result = self::save_problem($problem, $transaction);
      }
    }
    catch (Exception $e) {
      $transaction->rollback();
      \Drupal::logger('node_accessibility')->error("Failed to replace problem for nid=@nid, vid=@vid.", ['@nid' => $nid, '@vid' => $vid]);

      return FALSE;
    }
    catch (Error $e) {
      $transaction->rollback();
      \Drupal::logger('node_accessibility')->error("Failed to replace problem for nid=@nid, vid=@vid.", ['@nid' => $nid, '@vid' => $vid]);

      return FALSE;
    }

    return $result;
  }

  /**
   * This stores a validation problem for a given node to the database.
   *
   * @param array $problem_data
   *   An array of test data with the following keys:
   *   - nid: The node id of the node.
   *   - vid: The node revision id of the node.
   *   - test_id: The id of the problem.
   *   - test_severity: The severity of the problem.
   *   - line: The line number of the problem.
   *   - element: The html markup of the problem.
   * @param object|null $transaction
   *   (optional) A valid database transaction object.
   *
   * @return int|false
   *   The return states of either FALSE, SAVED_NEW, or SAVED_UPDATED.
   */
  public static function save_problem($problem_data, Transaction $transaction = NULL) {
    if (!is_array($problem_data)) {
      return FALSE;
    }

    $result = FALSE;
    $columns = ['nid', 'vid', 'test_id', 'test_severity', 'line', 'element'];

    foreach ($columns as $key) {
      if (empty($problem_data[$key])) {
        return FALSE;
      }
    }

    $data = [];
    $primary_key = [];
    $results = FALSE;

    if (!empty($problem_data['id'])) {
      if (!is_numeric($problem_data['id'])) {
        return FALSE;
      }

      $results = self::load_problems(['id' => $problem_data['id']], NULL);

      if (is_array($results) && !empty($results)) {
        $data['id'] = $problem_data['id'];
        $primary_key[] = 'id';
      }
      else {
        // if a specific id is requested but does not exist, then it cannot be updated.
        return FALSE;
      }
    }

    $data['nid'] = $problem_data['nid'];
    $data['vid'] = $problem_data['vid'];
    $data['test_id'] = $problem_data['test_id'];
    $data['test_severity'] = $problem_data['test_severity'];
    $data['line'] = $problem_data['line'];
    $data['element'] = $problem_data['element'];

    if (is_null($transaction)) {
      $transaction = \Drupal::database()->startTransaction();
    }

    try {
      $result = \Drupal::database()->insert('node_accessibility_problems')
        ->fields($data)
        ->execute();
    }
    catch (Exception $e) {
      $transaction->rollback();
      \Drupal::logger('node_accessibility')->error("Failed to save problem.");

      return FALSE;
    }
    catch (Error $e) {
      $transaction->rollback();
      \Drupal::logger('node_accessibility')->error("Failed to save problem.");

      return FALSE;
    }

    return $result;
  }

  /**
   * This restructures an array of problems as returned from database calls.
   *
   * The data is converted into a format that can be processed by the quail api
   * theme functions.
   *
   * @param int $nid
   *   A node id the results belong to.
   * @param int $vid
   *   A node revision id the results belong to.
   * @param array|null $severitys
   *   (optional) An array of display levels as returned by
   *   QuailApiSettings::get_severity().
   *   If NULL is passed, then this will auto-call
   *   QuailApiSettings::get_severity().
   *
   * @return array
   *   An array of database results in a format that can be processed by the
   *   quail_api theme functions.
   */
  public static function restructure_results($nid, $vid, $severitys = NULL) {
    $problems = (array) self::load_problems(['nid' => $nid, 'vid' => $vid], NULL);
    $tests = QuailApiBase::load_tests([], 'id');
    $results = [];

    if (!is_array($severitys)) {
      $severitys = QuailApiSettings::get_severity();
    }

    foreach ($severitys as $key => $value) {
      if (empty($value['id'])) continue;

      $results[$value['id']] = ['total' => 0];
    }

    foreach ($problems as $problem_key => $problem_data) {
      if (!is_object($problem_data)) continue;

      $test = $tests[$problem_data->test_id];

      if (!isset($results[$test->severity][$test->machine_name])) {
        $results[$test->severity][$test->machine_name] = [];
        $results[$test->severity][$test->machine_name]['body'] = [];
        $results[$test->severity][$test->machine_name]['body']['title'] = $test->human_name;
        $results[$test->severity][$test->machine_name]['body']['description'] = $test->description;
        $results[$test->severity][$test->machine_name]['problems'] = [];
      }

      $problem = [];
      $problem['line'] = $problem_data->line;
      $problem['element'] = $problem_data->element;

      $results[$test->severity][$test->machine_name]['problems'][] = $problem;
      $results[$test->severity]['total']++;
    }

    return $results;
  }

  /**
   * Loads stats for a particular node validation result.
   *
   * @param int $nid
   *   The node id.
   * @param int $vid
   *   The node revision id.
   *
   * @return array
   *   An array of database results.
   */
  public static function load_problem_stats($nid, $vid) {
    $results = [
      'nid' => NULL,
      'vid' => NULL,
      'uid' => NULL,
      'timestamp' => NULL,
    ];

    if (!is_int($nid) || !is_int($vid)) {
      return $results;
    }

    $results['nid'] = $nid;
    $results['vid'] = $vid;

    try {
      $query = \Drupal::database()->select('node_accessibility_stats', 'nas');
      $query->fields('nas', ['uid', 'timestamp']);
      $query->condition('nas.nid', $nid);
      $query->condition('nas.vid', $vid);

      $result = $query->execute()->fetchObject();
      if ($result) {
        $results['uid'] = $result->uid;
        $results['timestamp'] = $result->timestamp;
      }
    }
    catch (Exception $e) {
      $transaction->rollback();
      \Drupal::logger('node_accessibility')->error("Failed to load accessibility validation stats for nid=@nid, vid=@vid.", ['@nid' => $nid, '@vid' => $vid]);
    }
    catch (Error $e) {
      $transaction->rollback();
      \Drupal::logger('node_accessibility')->error("Failed to load accessibility validation stats for nid=@nid, vid=@vid.", ['@nid' => $nid, '@vid' => $vid]);
    }

    return $results;
  }

  /**
   * Insert or Update accessibility problem stats.
   *
   * @param int $uid
   *   The user account id associated with the stat.
   * @param int $nid
   *   The node id.
   * @param int $vid
   *   The node revision id.
   * @param int $timestamp
   *   The unix timestamp for the stat.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public static function replace_problem_stats($uid, $nid, $vid, $timestamp) {
    if (!is_int($uid) || !is_int($nid) || !is_int($vid) || !is_int($timestamp)) {
      return FALSE;
    }

    $result = FALSE;

    $transaction = \Drupal::database()->startTransaction();
    try {
      $query = \Drupal::database()->delete('node_accessibility_stats');
      $query->condition('nid', $nid);
      $query->condition('vid', $vid);
      $query->execute();

      $data['uid'] = $uid;
      $data['nid'] = $nid;
      $data['vid'] = $vid;
      $data['timestamp'] = $timestamp;

      $result = \Drupal::database()->insert('node_accessibility_stats')
        ->fields($data)
        ->execute();
    }
    catch (Exception $e) {
      $transaction->rollback();
      \Drupal::logger('node_accessibility')->error("Failed to replace accessibility validation stats for nid=@nid, vid=@vid.", ['@nid' => $nid, '@vid' => $vid]);

      return FALSE;
    }
    catch (Error $e) {
      $transaction->rollback();
      \Drupal::logger('node_accessibility')->error("Failed to replace accessibility validation stats for nid=@nid, vid=@vid.", ['@nid' => $nid, '@vid' => $vid]);

      return FALSE;
    }

    return $result;
  }
}
