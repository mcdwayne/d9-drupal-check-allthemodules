<?php

namespace Drupal\Tests\authorization_code_api\Functional;

use Behat\Mink\Driver\GoutteDriver;
use Drupal\Core\Database\Database;
use Drupal\Core\Test\TestDatabase;
use Drupal\Tests\BrowserTestBase;

/**
 * Test class for the API implementation of authorization code.
 *
 * @group authorization_code
 */
class LoginProcessApiTest extends BrowserTestBase {

  const TEST_CODE = '012345';
  const LOGIN_PROCESS_ENTITY_ID = 'static_email_watchdog';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'dblog',
    'authorization_code_api',
    'authorization_code_login_process_test',
  ];

  /**
   * Test user.
   *
   * @var \Drupal\user\Entity\User
   */
  private $testUser;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp() {
    parent::setUp();
    ($this->testUser = $this->createUser())
      ->setEmail($this->randomEmail())
      ->save();
    $this->prepareRequest();
  }

  /**
   * Tests start-login-process with missing identifier.
   */
  public function testStartWithMissingIdentifier() {
    $client = $this->getDriver()->getClient();
    $client->request(
      'POST',
      $this->buildUrl(static::startLoginProcessPath()),
      [],
      [],
      ['HTTP_Content-Type' => 'application/json'],
      '[]'
    );

    $this->assertEquals(400, $this->getSession()
      ->getStatusCode(), 'Response code is 400 - user error');
  }

  /**
   * Tests start-login-process with missing identifier.
   */
  public function testStartWithMissingUser() {
    $client = $this->getDriver()->getClient();
    $client->request(
      'POST',
      $this->buildUrl(static::startLoginProcessPath()),
      [],
      [],
      ['HTTP_Content-Type' => 'application/json'],
      json_encode(['identifier' => $this->randomEmail()])
    );

    $this->assertEquals(200, $this->getSession()
      ->getStatusCode(), 'Response code is 200 - missing user is ignored');

    ($query = static::getTestDbConnection()->select('watchdog'))
      ->condition('type', 'authorization_code')
      ->condition('message', 'Code: @code');
    $query->fields('watchdog')->countQuery();
    $this->assertEquals(0, $query->execute()
      ->fetchField(), 'No code was logged in DB.');
  }

  /**
   * Tests start-login-process with missing identifier.
   */
  public function testStartWithInvalidCode() {
    $client = $this->getDriver()->getClient();
    $client->request(
      'POST',
      $this->buildUrl(static::startLoginProcessPath()),
      [],
      [],
      ['HTTP_Content-Type' => 'application/json'],
      json_encode(['identifier' => $this->testUser->getEmail()])
    );

    $this->assertEquals(
      200,
      $this->getSession()->getStatusCode(),
      'Response code is 200'
    );

    ($query = static::getTestDbConnection()->select('watchdog'))
      ->condition('type', 'authorization_code')
      ->condition('message', 'Code: @code');
    $query->fields('watchdog', ['variables']);
    $variables = unserialize($query->execute()->fetchField());
    $code = $variables['@code'];
    $this->assertEquals(static::TEST_CODE, $code);

    $client->request(
      'POST',
      $this->buildUrl(static::completeLoginProcessPath()),
      [],
      [],
      ['HTTP_Content-Type' => 'application/json'],
      json_encode([
        'identifier' => $this->testUser->getEmail(),
        'code' => $this->randomString(),
      ])
    );
  }

  /**
   * Tests start-login-process with missing identifier.
   */
  public function testStartWithValidCode() {
    $client = $this->getDriver()->getClient();
    $client->request(
      'POST',
      $this->buildUrl(static::startLoginProcessPath()),
      [],
      [],
      ['HTTP_Content-Type' => 'application/json'],
      json_encode(['identifier' => $this->testUser->getEmail()])
    );

    $this->assertEquals(
      200,
      $this->getSession()->getStatusCode(),
      'Response code is 200'
    );

    ($query = static::getTestDbConnection()->select('watchdog'))
      ->condition('type', 'authorization_code')
      ->condition('message', 'Code: @code');
    $query->fields('watchdog', ['variables']);
    $variables = unserialize($query->execute()->fetchField());
    $code = $variables['@code'];
    $this->assertEquals(static::TEST_CODE, $code);

    $client->request(
      'POST',
      $this->buildUrl(static::completeLoginProcessPath()),
      [],
      [],
      ['HTTP_Content-Type' => 'application/json'],
      json_encode([
        'identifier' => $this->testUser->getEmail(),
        'code' => $code,
      ])
    );
  }

  /**
   * The start-login-process API path.
   *
   * @return string
   *   The start-login-process API path.
   */
  private static function startLoginProcessPath(): string {
    return sprintf("/user/%s/start-login-process", static::LOGIN_PROCESS_ENTITY_ID);
  }

  /**
   * The complete-login-process API path.
   *
   * @return string
   *   The complete-login-process API path.
   */
  private static function completeLoginProcessPath(): string {
    return sprintf("/user/%s/complete-login-process", static::LOGIN_PROCESS_ENTITY_ID);
  }

  /**
   * Returns the database connection to the site running Simpletest.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection to use for inserting assertions.
   */
  private static function getTestDbConnection() {
    $db_prefix = (new TestDatabase())->getDatabasePrefix();

    Database::addConnectionInfo(
      $db_prefix,
      $db_prefix,
      array_merge(
        Database::getConnectionInfo()['default'],
        ['prefix' => ['default' => $db_prefix]]
      )
    );

    return Database::getConnection($db_prefix);
  }

  /**
   * Constructs a random email string.
   *
   * @return string
   *   The random email string.
   */
  private function randomEmail(): string {
    return sprintf('%s@%s', $this->randomMachineName(), $this->randomMachineName());
  }

  /**
   * The session driver.
   *
   * @return \Behat\Mink\Driver\GoutteDriver
   *   The session driver.
   */
  private function getDriver(): GoutteDriver {
    return $this->getSession()->getDriver();
  }

}
