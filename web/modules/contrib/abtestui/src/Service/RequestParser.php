<?php

namespace Drupal\abtestui\Service;

use Drupal\Core\Database\Connection;
use PDO;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RequestParser.
 *
 * @package Drupal\abtestui\Service
 */
class RequestParser {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * The current request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * The abjs config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $abjsConfig;

  /**
   * RequestParser constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   */
  public function __construct(Connection $database, RequestStack $requestStack) {
    $this->database = $database;
    $this->request = $requestStack->getCurrentRequest();
    $this->abjsConfig = \Drupal::config('abjs.settings');
  }

  /**
   * Load the appropriate test.
   *
   * Loads the test for the current variation or base_url.
   *
   * @return array|bool
   *   Return the tid and name of the test, or FALSE if it's not found.
   */
  public function getTest() {
    // Search for a suitable test.
    $query = $this->database->select(TestStorage::BASE_TABLE_NAME, 'base_table');
    $query->join(AbjsTestStorage::BASE_TABLE_NAME, 'test', 'base_table.tid = test.tid');
    $query->join(VariationStorage::BASE_TABLE_NAME, 'variation', 'variation.tid = test.tid');
    // No need to bother with inactive ones.
    $query->condition('test.active', 1);
    // We are either on a control (base_url) or a variation (variation.url).
    // In both cases, we need the test tid and name.
    $currPath = '%' . $this->request->getPathInfo();
    $pathCondition = $query->orConditionGroup()
      ->condition('variation.url', $currPath, 'like')
      ->condition('base_table.base_url', $currPath, 'like');
    $query->condition($pathCondition);
    $query->fields('test', ['tid', 'name']);
    $result = $query->execute();
    return $result->fetchAssoc();
  }

  /**
   * Get the experience from the cookies, or every experience for the test.
   *
   * @param int|string $tid
   *   The test tid.
   * @param string $cookieName
   *   The name of the cookie, e.g. abjs_t_1.
   *
   * @return array
   *   An array of experience names keyed with 'e_' prefixed eids, or an
   *   empty array.
   */
  public function getExperiences($tid, $cookieName) {
    $experiences = [];
    // If the cookie exists, we need to load the name of only that experience.
    if ($this->request->cookies->has($cookieName)) {
      $experience = $this->request->cookies->get($cookieName);
      $eid = str_replace('e_', '', $experience);

      $query = $this->database->select(AbjsExperienceStorage::BASE_TABLE_NAME, 'base_table');
      // We check only for the eid, no need to check for the test id.
      $query->condition('base_table.eid', $eid);
      $query->addField('base_table', 'name');
      $result = $query->execute();
      $row = $result->fetchAssoc();
      $experiences[$experience] = $row['name'];
    }
    // Otherwise, we need to load all of them for the given test.
    else {
      $query = $this->database->select(AbjsExperienceStorage::BASE_TABLE_NAME, 'base_table');
      $query->join(AbjsTestStorage::EXPERIENCE_RELATION_TABLE, 'relation', 'relation.eid = base_table.eid');
      $query->condition('relation.tid', $tid);
      $query->fields('base_table', ['eid', 'name']);
      $result = $query->execute();
      /** @var array[] $rows */
      $rows = $result->fetchAllAssoc('eid', PDO::FETCH_ASSOC);

      /** @var array $row */
      foreach ($rows as $row) {
        $experiences['e_' . $row['eid']] = $row['name'];
      }
    }
    return $experiences;
  }

  /**
   *
   */
  public function generateGathererScript() {
    $test = $this->getTest();

    // If no active test is found, don't do anything.
    if (FALSE === $test || !isset($test['tid'])) {
      return FALSE;
    }

    $testKey = 't_' . $test['tid'];
    $cookiePrefix = $this->abjsConfig->get('cookie.prefix');
    $cookieName = $cookiePrefix . $testKey;
    $experiences = $this->getExperiences($test['tid'], $cookieName);

    // If we found the cookie, no need for client side cookie parsing.
    // We generate the required JS code on server side.
    if ($this->request->cookies->has($cookieName)) {
      $cookieGathererScript = "\nfunction getTestDataForUser() {\n";
      $cookieGathererScript .= "\treturn \"" . $test['name'] . ' | ' . reset($experiences) . "\";\n";
      $cookieGathererScript .= "}\n";
      return $cookieGathererScript;
    }

    // Generate a map for the JS from the test and experience data.
    $mapValues = [
      $testKey => [$testKey => $test['name']] + $experiences + ['=' => ' | '],
    ];
    $mapValues = json_encode($mapValues);

    // Get the gatherer JS code.
    $cookieGathererScript = file_get_contents(drupal_get_path('module', 'abtestui') . '/js/gatherCookieData.js');
    // Replace the placeholders as needed.
    $cookieGathererScript = str_replace(
      ['{{ cookie_prefix }}', "'{{ test_replace_map_content }}'"],
      [$cookiePrefix, $mapValues],
      $cookieGathererScript
    );

    return $cookieGathererScript;
  }

}
