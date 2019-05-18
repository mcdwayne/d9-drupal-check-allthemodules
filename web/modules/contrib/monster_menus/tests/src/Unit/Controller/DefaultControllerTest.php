<?php

namespace Drupal\Tests\monster_menus\Unit\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\monster_menus\Controller\DefaultController;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\monster_menus\Controller\DefaultController
 * @group monster_menus
 */
class DefaultControllerTest extends UnitTestCase {

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->database = $this->getMockBuilder('\Drupal\Core\Database\Connection')
                            ->disableOriginalConstructor()
                            ->getMock();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  /**
   * Test the static create method.
   */
  public function testCreate() {
    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->expects($this->any())
      ->method('get')
      ->will($this->onConsecutiveCalls($this->database));

    $this->assertInstanceOf('\Drupal\monster_menus\Controller\DefaultController', DefaultController::create($container));
  }

  /**
   * Test for 'menuAccessCreateHomepage'.
   *
   * @covers ::menuAccessCreateHomepage
   *
   * @dataProvider providerTestMenuAccessCreateHomepage
   */
  /*
   * @TODO: handle mm_get_setting() so this test doesn't fail to run.
  public function testMenuAccessCreateHomepage(AccountInterface $user, User $account, AccessResult $expected_result) {

    $container = new ContainerBuilder();
    $container->set('current_user', $user);
    \Drupal::setContainer($container);

    $this->assertEquals($expected_result, DefaultController::menuAccessCreateHomepage($account));
  }
  */

  /**
   * Data provider for ::testMenuAccessCreateHomepage.
   */
  public function providerTestMenuAccessCreateHomepage() {

    $cases = [];

    // Test if the account isn't active.
    $user = $this->prophesize(AccountInterface::class);
    $user->hasPermission('administer all users')->willReturn(TRUE);
    $account = $this->prophesize(User::class);
    $account->isActive()->willReturn(FALSE);
    $cases[] = [$user->reveal(), $account->reveal(), AccessResult::neutral()];

    // Test if the account is active and the current user has admin permissions.
    $user = $this->prophesize(AccountInterface::class);
    $user->hasPermission('administer all users')->willReturn(TRUE);
    $account = $this->prophesize(User::class);
    $account->isActive()->willReturn(TRUE);
    $cases[] = [$user->reveal(), $account->reveal(), AccessResult::allowed()];

    // Test if the account is active and doesn't have admin permissions, but the ids match.
    $user = $this->prophesize(AccountInterface::class);
    $user->hasPermission('administer all users')->willReturn(FALSE);
    $user->id()->willReturn(25);
    $account = $this->prophesize(User::class);
    $account->isActive()->willReturn(TRUE);
    $account->id()->willReturn(25);
    $cases[] = [$user->reveal(), $account->reveal(), AccessResult::allowed()];

    // Test if the account is active and doesn't have admin permissions, and the ids don't match.
    $user = $this->prophesize(AccountInterface::class);
    $user->hasPermission('administer all users')->willReturn(FALSE);
    $user->id()->willReturn(26);
    $account = $this->prophesize(User::class);
    $account->isActive()->willReturn(TRUE);
    $account->id()->willReturn(25);
    $cases[] = [$user->reveal(), $account->reveal(), AccessResult::neutral()];

    return $cases;
  }

  /**
   * Test for accessAnyAdmin'.
   *
   * @covers ::accessAnyAdmin
   *
   * @dataProvider providerTestAccessAnyAdmin
   */
  public function testAccessAnyAdmin(AccountInterface $account, $expected_result) {
    $this->assertEquals($expected_result, DefaultController::accessAnyAdmin($account));
  }

  /**
   * Data provider for ::testAccessAnyAdmin.
   */
  public function providerTestAccessAnyAdmin() {

    $cases = [];

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('administer all groups')->willReturn(TRUE);
    $account->hasPermission('administer all users')->willReturn(FALSE);
    $account->hasPermission('administer all menus')->willReturn(FALSE);
    $cases[] = [$account->reveal(), AccessResult::allowed()];

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('administer all groups')->willReturn(FALSE);
    $account->hasPermission('administer all users')->willReturn(TRUE);
    $account->hasPermission('administer all menus')->willReturn(FALSE);
    $cases[] = [$account->reveal(), AccessResult::allowed()];

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('administer all groups')->willReturn(FALSE);
    $account->hasPermission('administer all users')->willReturn(FALSE);
    $account->hasPermission('administer all menus')->willReturn(TRUE);
    $cases[] = [$account->reveal(), AccessResult::allowed()];

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('administer all groups')->willReturn(FALSE);
    $account->hasPermission('administer all users')->willReturn(FALSE);
    $account->hasPermission('administer all menus')->willReturn(FALSE);
    $cases[] = [$account->reveal(), AccessResult::neutral()];

    return $cases;
  }

  /**
   * Test for accessAllAdmin'.
   *
   * @covers ::accessAllAdmin
   *
   * @dataProvider providerTestAccessAllAdmin
   */
  public function testAccessAllAdmin(AccountInterface $account, $expected_result) {
    $this->assertEquals($expected_result, DefaultController::accessAllAdmin($account));
  }

  /**
   * Data provider for ::testAccessAllAdmin.
   */
  public function providerTestAccessAllAdmin() {

    $cases = [];

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('administer all groups')->willReturn(TRUE);
    $account->hasPermission('administer all users')->willReturn(TRUE);
    $account->hasPermission('administer all menus')->willReturn(TRUE);
    $cases[] = [$account->reveal(), AccessResult::allowed()];

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('administer all groups')->willReturn(TRUE);
    $account->hasPermission('administer all users')->willReturn(FALSE);
    $account->hasPermission('administer all menus')->willReturn(FALSE);
    $cases[] = [$account->reveal(), AccessResult::neutral()];

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('administer all groups')->willReturn(FALSE);
    $account->hasPermission('administer all users')->willReturn(TRUE);
    $account->hasPermission('administer all menus')->willReturn(FALSE);
    $cases[] = [$account->reveal(), AccessResult::neutral()];

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('administer all groups')->willReturn(FALSE);
    $account->hasPermission('administer all users')->willReturn(FALSE);
    $account->hasPermission('administer all menus')->willReturn(TRUE);
    $cases[] = [$account->reveal(), AccessResult::neutral()];

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('administer all groups')->willReturn(FALSE);
    $account->hasPermission('administer all users')->willReturn(FALSE);
    $account->hasPermission('administer all menus')->willReturn(FALSE);
    $cases[] = [$account->reveal(), AccessResult::neutral()];

    return $cases;
  }

}