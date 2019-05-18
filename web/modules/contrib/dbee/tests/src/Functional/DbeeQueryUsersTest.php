<?php

namespace Drupal\Tests\dbee\Functional;

use Drupal\Core\Database\Query\Condition;
use Drupal\KernelTests\AssertLegacyTrait;

/**
 * Database queries on user emails.
 *
 *  May be used by contrib modules.
 *
 * @group dbee
 */
class DbeeQueryUsersTest extends DbeeWebSwitchTestBase {

  /**
   * User 2.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

  /**
   * User 3.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user3;

  /**
   * User 4.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user4;

  /**
   * Query results.
   *
   * @var array
   */
  protected $queryResults = [];

  /**
   * User view users.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userViewUsers;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dbee'];

  /**
   * Create users with appropriate permissions.
   *
   * {@inheritdoc}
   */
  public function setUp() {
    // Enable any modules required for the test.
    parent::setUp();
    // Create a user who can enable the dbee module.
    $this->adminModulesAccount = $this->drupalCreateUser(['administer modules']);
    $this->userViewUsers = $this->drupalCreateUser(['access user profiles']);

    $this->user2 = $this->drupalCreateUser();
    $this->user3 = $this->drupalCreateUser();
    $this->user4 = $this->drupalCreateUser();

    // drupalCreateUser() set an empty 'init' value. Fix it.
    $this->user2->set('init', $this->randomMachineName() . '@example.com')
      ->save();
    $this->user3->set('init', 'an_invalid_email')->save();
    $this->user4->set('init', $this->randomMachineName() . '@example.com')
      ->save();
  }

  /**
   * Test multiples queries on emails.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testQueryFunctions() {
    $connection = $this->container->get('database');
    $test_title = $verbose = [];
    foreach ([1, 0] as $dbee) {

      if (!$dbee) {
        // Uninstall the dbee module.
        $this->dbeeEnablingDisablingDbeeModule(FALSE);

      }

      $this->drupalLogin($this->userViewUsers);
      foreach (['mail', 'init'] as $dbee_field) {
        $keys = NULL;
        // Reset to default operator.
        $operator = '=';
        for ($test = 1; $test <= 16; $test++) {
          switch ($test) {
            case 1:
              // Look for NULL.
              $test_title[$test] = 'querying empty mail or init value, operator \'IS NULL\'.';
              $operator = 'IS NULL';
              break;

            case 2:
              // Look for NOT NULL.
              $test_title[$test] = 'querying empty mail or init value, operator \'IS NOT NULL\'.';
              $operator = 'IS NOT NULL';
              break;

            case 3:
              // Look for empty sting.
              $test_title[$test] = 'querying empty mail or init value, operator \'=\'.';
              $keys = '';
              $operator = '=';
              break;

            case 4:
              // Look for empty sting.
              $test_title[$test] = 'querying empty mail or init value, operator \'<>\'.';
              // $keys same than test 1.
              $operator = '<>';
              break;

            case 5:
              // Test searching 1 whole email.
              $test_title[$test] = 'querying a user by mail or init exact value, operator \'=\'.';
              $keys = $this->user2->{$dbee_field}->value;
              $operator = '=';
              break;

            case 6:
              // Test searching 1 whole email.
              $test_title[$test] = 'querying a user by mail or init exact value, operator \'<>\'.';
              // $keys same than test 3.
              $operator = '<>';
              break;

            case 7:
              // Test searching 1 whole mail email value changing case.
              $test_title[$test] = 'querying a user by mail or init with sensitive case conflict value, operator \'=\'.';
              $keys = mb_strtoupper($this->user2->{$dbee_field}->value);
              $operator = '=';
              break;

            case 8:
              // Test searching 1 whole mail email value changing case.
              $test_title[$test] = 'querying a user by mail or init with sensitive case conflict value, operator \'<>\'.';
              // $keys same than test 5.
              $operator = '<>';
              break;

            case 9:
              // Test searching an email value changing case with 'like'
              // operator.
              // $keys is the same as test 5 and 6.
              $test_title[$test] = 'querying a user by mail or init with sensitive case conflict value, operator \'LIKE\'.';
              $operator = 'LIKE';
              break;

            case 10:
              // Test searching an email value changing case with 'like'
              // operator.
              // $keys is the same as test 5, 6 and 7.
              $test_title[$test] = 'querying a user by mail or init with sensitive case conflict value, operator \'NOT LIKE\'.';
              $operator = 'NOT LIKE';
              break;

            case 11:
              // Test searching 2 whole email.
              $test_title[$test] = 'querying 2 user by mail or init exact value, operator \'IN\'.';
              $keys = [
                $this->user2->{$dbee_field}->value,
                $this->user3->{$dbee_field}->value,
              ];
              $operator = 'IN';
              break;

            case 12:
              // Test searching 2 whole email.
              // $keys is the same than test 9.
              $test_title[$test] = 'querying 2 user by mail or init exact value, operator \'NOT IN\'.';
              $operator = 'NOT IN';
              break;

            case 13:
              // Test searching 3 users  by a partial key.
              $keys = '%exAM_le.com%';
              $test_title[$test] = 'querying users by mail or init value using wildcard (\'' . $keys . '\'), operator \'LIKE\'.';
              $operator = 'LIKE';
              break;

            case 14:
              // Test searching 3 users  by a partial key.
              // $keys is the same than test 11.
              $test_title[$test] = 'querying users by mail or init value using wildcard (\'' . $keys . '\'), operator \'NOT LIKE\'.';
              $operator = 'NOT LIKE';
              break;

            case 15:
              // Using nested conditions.
              $test_title[$test] = 'querying users by mail or init value using wildcard  (\'' . $keys . '\') on nested conditions, operator \'LIKE\'.';
              // $keys is the same than test 11 and 12.
              $operator = 'LIKE';
              break;

            case 16:
              // Using nested conditions.
              $test_title[$test] = 'querying users by mail or init value using wildcard  (\'' . $keys . '\') on nested conditions, operator \'NOT LIKE\'.';
              // $keys is the same than test 11, 12 and 13.
              $operator = 'NOT LIKE';
              break;

          }

          $query = $connection->select('users_field_data', 'u');
          $query->fields('u', [
            'uid',
            'mail',
            'name',
            'status',
            'created',
            'access',
          ]);

          if ($test < 15) {
            if (!in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
              $query->condition("u.$dbee_field", $keys, $operator);
            }
            elseif ($operator == 'IS NULL') {
              $query->isNull("u.$dbee_field");
            }
            elseif ($operator == 'IS NOT NULL') {
              $query->isNotNull("u.$dbee_field");
            }
          }
          else {
            $query->condition((new Condition('OR'))
              ->condition('name', 'Not exists', '=')
              ->condition($dbee_field, $keys, $operator));
          }
          // The hook_query_alter function only works on dynamic tagged queries.
          $query->addTag('test');
          if (!$dbee) {
            $query->addTag('dbee_disabled');
          }
          $query->orderBy('uid');
          $result = $query->execute();
          $result_uids = [];
          foreach ($result as $record) {
            $result_uids[] = $record->uid;
          }
          // Save the test result.
          $query_args = $query->arguments();
          $this->queryResults[$test][$dbee][$dbee_field] = $result_uids;
          $verbose[$test][$dbee][$dbee_field] = "test #{$test}, dbee " . (($dbee) ? 'enabled' : 'uninstalled') . ", field : {$dbee_field}" .
            (($dbee) ? '' : (($this->queryResults[$test][0][$dbee_field] == $this->queryResults[$test][1][$dbee_field]) ? "\n" . 'Test succeed' : "\n" . '<strong>Test Failed</strong>')) .
            "\n" . (($dbee) ? 'Transformed query' : 'Query') . ' --> ' . strtr($query->__toString(), $query_args) .
            "\n" . ' ===> returned users ids : ' . implode(' ,', $result_uids);
        }
      }
    }

    // Explore results.
    foreach ($this->queryResults as $test => $data) {
      foreach (['mail', 'init'] as $dbee_field) {
        $debug = "\n\n\n" . $verbose[$test][0][$dbee_field] . "\n\n" . $verbose[$test][1][$dbee_field];
        $this->assertEquals($data[0][$dbee_field], $data[1][$dbee_field], "test #{$test} succeed : {$test_title[$test]} ({$dbee_field})" . $debug);
      }
    }
  }

}
