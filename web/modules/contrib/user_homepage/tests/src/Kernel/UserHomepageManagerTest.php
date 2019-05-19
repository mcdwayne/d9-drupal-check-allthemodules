<?php

namespace Drupal\Tests\user_homepage\Unit;

use Drupal;
use Drupal\Core\Session\UserSession;
use Drupal\KernelTests\KernelTestBase;
use stdClass;

/**
 * Tests the UserHomepageManager class.
 *
 * @group user_homepage
 */
class UserHomepageManagerTest extends KernelTestBase {

  /**
   * The user homepage manager class to test.
   *
   * @var \Drupal\user_homepage\UserHomepageManager
   */
  private $userHomepageManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user_homepage'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('user_homepage', 'user_homepage');
    $this->userHomepageManager = Drupal::service('user_homepage.manager');
  }

  /**
   * Tests retrieving the user homepage.
   */
  public function testGetUserHomepage() {
    // Ensure null is returned if there is no homepage set.
    $this->assertNull($this->userHomepageManager->getUserHomepage(2));

    Drupal::database()->insert('user_homepage')->fields(['uid' => 2, 'path' => '/test?param=param'])->execute();
    $this->assertEquals('/test?param=param', $this->userHomepageManager->getUserHomepage(2));
  }

  /**
   * Tests setting the user homepage.
   */
  public function testSetUserHomepage() {
    // Set path and check actual value stored.
    $this->assertTrue($this->userHomepageManager->setUserHomepage(2, '/first-path'));
    $actual = Drupal::database()->query("SELECT path FROM {user_homepage} WHERE uid=2")->fetchField();
    $this->assertEquals('/first-path', $actual);

    // Set path again and check it's been updated correctly.
    $this->assertTrue($this->userHomepageManager->setUserHomepage(2, '/new-path'));
    $actual = Drupal::database()->query("SELECT path FROM {user_homepage} WHERE uid=2")->fetchField();
    $this->assertEquals('/new-path', $actual);

    // Ensure FALSE is returned when a problem / Exception occurs.
    $wrong_args_format = ['wrong', 'arguments', 'format'];
    $this->assertFalse($this->userHomepageManager->setUserHomepage($wrong_args_format, '/path?'));
  }

  /**
   * Tests unsetting the user homepage.
   */
  public function testUnsetUserHomepage() {
    // Set path.
    $this->userHomepageManager->setUserHomepage(2, '/i-will-be-unset');

    // Ensure FALSE is returned if something goes wrong.
    $this->assertFalse($this->userHomepageManager->unsetUserHomepage(new stdClass()));

    // Ensure path is unset correctly.
    $this->assertTrue($this->userHomepageManager->unsetUserHomepage(2));
    $this->assertFalse(Drupal::database()->query("SELECT path FROM {user_homepage} WHERE uid=2")->fetchField());
  }

  /**
   * Tests the user homepage path is built correctly based on current request.
   */
  public function testBuildHomepagePathFromCurrentRequest() {
    Drupal::service('path.current')->setPath('/node');
    Drupal::requestStack()->getCurrentRequest()->query->set('param1', 'one');
    Drupal::requestStack()->getCurrentRequest()->query->set('param2', 'two');

    $this->assertEquals('/node?param1=one&param2=two', $this->userHomepageManager->buildHomepagePathFromCurrentRequest());
  }

  /**
   * Tests user redirect after login is resolved correctly.
   */
  public function testResolveUserRedirection() {
    $account = new UserSession(['uid' => 1]);

    // Check no redirection is done if user doesn't have a homepage.
    $this->assertFalse($this->userHomepageManager->resolveUserRedirection($account));

    // Check redirection is triggered for the correct page when user has a
    // homepage.
    $this->userHomepageManager->setUserHomepage(1, '/test?param1=one&param2=two');
    $this->assertTrue($this->userHomepageManager->resolveUserRedirection($account));
    $this->assertEquals('/test?param1=one&param2=two', Drupal::requestStack()->getCurrentRequest()->query->get('destination'));
  }

}
