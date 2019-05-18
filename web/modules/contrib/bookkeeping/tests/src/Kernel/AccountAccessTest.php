<?php

namespace Drupal\Tests\bookkeeping\Kernel;

use Drupal\bookkeeping\Entity\Account;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Test access controls on accounts.
 *
 * @group bookkeeping
 */
class AccountAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * An anonymous user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userAnon;

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userAuth;

  /**
   * An authenticated user with the view bookkeeping permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userView;

  /**
   * An authenticated user with the manage bookkeeping permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userManage;

  /**
   * An authenticated user with the administer bookkeeping permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userAdminister;

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userAdmin;

  /**
   * An account entity.
   *
   * @var \Drupal\bookkeeping\Entity\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'bookkeeping',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
    $this->installConfig('bookkeeping');

    // Create a user to get past user 1.
    $this->createUser();

    // Generate the users we'll test.
    $this->userAnon = User::getAnonymousUser();
    $this->userAuth = $this->createUser();
    $this->userView = $this->createUser(['view bookkeeping']);
    $this->userManage = $this->createUser(['manage bookkeeping']);
    $this->userAdminister = $this->createUser(['administer bookkeeping']);
    $this->userAdmin = $this->createUser([], NULL, TRUE);

    // Create an account.
    $this->account = Account::create([
      'id' => 'account',
      'label' => 'Account',
    ]);
    $this->account->save();
  }

  /**
   * Test create access.
   */
  public function testCreateAccess() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $access = $entity_type_manager
      ->getAccessControlHandler('bookkeeping_account');

    $result = $access->createAccess(NULL, $this->userAnon);
    $this->assertFalse($result, 'Anonymous');

    $result = $access->createAccess(NULL, $this->userAuth);
    $this->assertFalse($result, 'Authenticated');

    $result = $access->createAccess(NULL, $this->userView);
    $this->assertFalse($result, 'View permission');

    $result = $access->createAccess(NULL, $this->userManage);
    $this->assertTrue($result, 'Manage permission');

    $result = $access->createAccess(NULL, $this->userAdminister);
    $this->assertTrue($result, 'Administer permission');

    $result = $access->createAccess(NULL, $this->userAdmin);
    $this->assertTrue($result, 'Admin');
  }

  /**
   * Test view access.
   */
  public function testViewAccess() {
    $result = $this->account->access('view', $this->userAnon);
    $this->assertFalse($result, 'Anonymous');

    $result = $this->account->access('view', $this->userAuth);
    $this->assertFalse($result, 'Authenticated');

    $result = $this->account->access('view', $this->userView);
    $this->assertTrue($result, 'View permission');

    $result = $this->account->access('view', $this->userManage);
    $this->assertTrue($result, 'Manage permission');

    $result = $this->account->access('view', $this->userAdminister);
    $this->assertTrue($result, 'Administer permission');

    $result = $this->account->access('view', $this->userAdmin);
    $this->assertTrue($result, 'Admin');
  }

  /**
   * Test update access.
   */
  public function testUpdateAccess() {
    $result = $this->account->access('update', $this->userAnon);
    $this->assertFalse($result, 'Anonymous');

    $result = $this->account->access('update', $this->userAuth);
    $this->assertFalse($result, 'Authenticated');

    $result = $this->account->access('update', $this->userView);
    $this->assertFalse($result, 'View permission');

    $result = $this->account->access('update', $this->userManage);
    $this->assertTrue($result, 'Manage permission');

    $result = $this->account->access('update', $this->userAdminister);
    $this->assertTrue($result, 'Administer permission');

    $result = $this->account->access('update', $this->userAdmin);
    $this->assertTrue($result, 'Admin');
  }

  /**
   * Test delete access.
   */
  public function testDeleteAccess() {
    $result = $this->account->access('delete', $this->userAnon);
    $this->assertFalse($result, 'Anonymous');

    $result = $this->account->access('delete', $this->userAuth);
    $this->assertFalse($result, 'Authenticated');

    $result = $this->account->access('delete', $this->userView);
    $this->assertFalse($result, 'View permission');

    $result = $this->account->access('delete', $this->userManage);
    $this->assertTrue($result, 'Manage permission');

    $result = $this->account->access('delete', $this->userAdminister);
    $this->assertTrue($result, 'Administer permission');

    $result = $this->account->access('delete', $this->userAdmin);
    $this->assertTrue($result, 'Admin');
  }

}
