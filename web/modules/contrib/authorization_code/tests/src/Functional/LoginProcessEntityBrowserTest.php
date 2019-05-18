<?php

namespace Drupal\Tests\authorization_code\Functional;

use Drupal\authorization_code\Entity\LoginProcess;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Login process entity browser test.
 *
 * @group authorization_code
 */
class LoginProcessEntityBrowserTest extends BrowserTestBase {

  const TEST_CODE = '1234';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'authorization_code',
    'authorization_code_login_process_test',
  ];

  /**
   * A user authorized to create/edit login process entities.
   *
   * @var \Drupal\user\UserInterface
   */
  private $authorizedUser;

  /**
   * A user not authorized to create/edit login process entities.
   *
   * @var \Drupal\user\UserInterface
   */
  private $unAuthorizedUser;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  private $testUser;

  /**
   * The login process entity base path.
   *
   * @var string
   */
  private $loginProcessBasePath;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp() {
    parent::setUp();
    $this->authorizedUser = $this->createUser(['administer site configuration']);
    $this->unAuthorizedUser = $this->createUser([]);
    $this->testUser = $this->createUser([], 'test_user');
    $this->loginProcessBasePath = $this->container->get('entity_type.manager')
      ->getDefinition('login_process')
      ->getLinkTemplate('collection');
  }

  /**
   * Tests authorized user has access to login process creation page.
   */
  public function testAuthorizedUserHasAccess() {
    $this->drupalLogin($this->authorizedUser);
    $this->drupalGet(Url::fromRoute('entity.login_process.add_form'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests unauthorized user has no access to login process creation page.
   */
  public function testUnAuthorizedUserHasNoAccess() {
    $this->drupalLogin($this->unAuthorizedUser);
    $this->drupalGet(Url::fromRoute('entity.login_process.add_form'));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests anonymous user has access to login process creation page.
   */
  public function testAnonymousUserHasNoAccess() {
    $this->drupalGet(Url::fromRoute('entity.login_process.add_form'));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test login process with id.
   */
  public function testLoginProcessWithId() {
    $login_process = LoginProcess::create($this->makeLoginProcessConfig('user_id'));
    $login_process->startLoginProcess($this->testUser->id());
    $login_process->completeLoginProcess($this->testUser->id(), static::TEST_CODE);
    $this->assertEquals($this->testUser->id(), \Drupal::currentUser()->id());
  }

  /**
   * Test login process with username.
   */
  public function testLoginProcessWithName() {
    $login_process = LoginProcess::create($this->makeLoginProcessConfig('username'));
    $login_process->startLoginProcess('test_user');
    $login_process->completeLoginProcess('test_user', static::TEST_CODE);
    $this->assertEquals($this->testUser->id(), \Drupal::currentUser()->id());
  }

  /**
   * Test login process with email.
   */
  public function testLoginProcessWithEmail() {
    $login_process = LoginProcess::create($this->makeLoginProcessConfig('email'));
    $login_process->startLoginProcess($this->testUser->getEmail());
    $login_process->completeLoginProcess($this->testUser->getEmail(), static::TEST_CODE);
    $this->assertEquals($this->testUser->id(), \Drupal::currentUser()->id());
  }

  /**
   * Create a login process configuration array.
   *
   * @param string $user_identifier_plugin_id
   *   The user identifier plugin id.
   *
   * @return array
   *   The login process configuration array.
   */
  private function makeLoginProcessConfig($user_identifier_plugin_id): array {
    return [
      'id' => 'test_login_process',
      'user_identifier' => [
        'plugin_id' => $user_identifier_plugin_id,
        'settings' => [],
      ],
      'code_generator' => [
        'plugin_id' => 'static_code',
        'settings' => ['code' => static::TEST_CODE],
      ],
      'code_sender' => ['plugin_id' => 'ignore'],
    ];
  }

}
