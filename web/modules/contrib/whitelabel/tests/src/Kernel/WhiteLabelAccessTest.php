<?php

namespace Drupal\Tests\whitelabel\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\whitelabel\Traits\WhiteLabelCreationTrait;
use Drupal\user\RoleInterface;
use Drupal\whitelabel\WhiteLabelInterface;

/**
 * Tests the access to certain functionality for different permissions.
 *
 * @group whitelabel
 */
class WhiteLabelAccessTest extends KernelTestBase {

  use WhiteLabelCreationTrait {
    createWhiteLabel as drupalCreateWhiteLabel;
  }
  use UserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
    createAdminRole as drupalCreateAdminRole;
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'text',
    'options',
    'user',
    'file',
    'image',
    'whitelabel',
  ];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('whitelabel');
    $this->installConfig(['whitelabel']);
    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('whitelabel');
    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)
      ->set('permissions', [])
      ->save();

    // Create user 1 who has special permissions.
    $this->drupalCreateUser();

    // Create a default white label.
    $this->drupalCreateWhiteLabel();
  }

  /**
   * Runs basic tests for white label access function.
   */
  public function testWhiteLabelAccess() {
    // Ensures user without any white label permission can do nothing.
    $web_user1 = $this->drupalCreateUser();
    $white_label1 = $this->drupalCreateWhiteLabel();
    $this->assertWhiteLabelCreateAccess(FALSE, $web_user1);
    $this->assertWhiteLabelAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
      'serve' => FALSE,
    ], $white_label1, $web_user1);

    // Ensures user with 'administer white label settings' permission can do
    // everything.
    $web_user2 = $this->drupalCreateUser(['administer white label settings']);
    $white_label2 = $this->drupalCreateWhiteLabel();
    $this->assertWhiteLabelCreateAccess(TRUE, $web_user2);
    $this->assertWhiteLabelAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
      'serve' => TRUE,
    ], $white_label2, $web_user2);

    // Ensures user with 'view white label pages' permission can view only.
    $web_user3 = $this->drupalCreateUser(['view white label pages']);
    $white_label3 = $this->drupalCreateWhiteLabel();
    $this->assertWhiteLabelCreateAccess(FALSE, $web_user3);
    $this->assertWhiteLabelAccess([
      'view' => TRUE,
      'update' => FALSE,
      'delete' => FALSE,
      'serve' => FALSE,
    ], $white_label3, $web_user3);

    // Ensures user with 'serve white label pages' permission can create, view
    // and update.
    $web_user4 = $this->drupalCreateUser(['serve white label pages']);
    $white_label4 = $this->drupalCreateWhiteLabel(['uid' => $web_user4->id()]);
    $this->assertWhiteLabelCreateAccess(TRUE, $web_user4);
    $this->assertWhiteLabelAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => FALSE,
      'serve' => TRUE,
    ], $white_label4, $web_user4);
  }

  /**
   * Test operations not supported by node grants.
   */
  public function testUnsupportedOperation() {
    $web_user = $this->drupalCreateUser(['view white label pages']);
    $white_label = $this->drupalCreateWhiteLabel();
    $this->assertWhiteLabelAccess(['random_operation' => FALSE], $white_label, $web_user);
  }

  /**
   * Asserts that node access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected node access grants for the node
   *   and account, with each key as the name of an operation (e.g. 'view',
   *   'delete') and each value a Boolean indicating whether access to that
   *   operation should be granted.
   * @param \Drupal\whitelabel\WhiteLabelInterface $white_label
   *   The white label object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   */
  public function assertWhiteLabelAccess(array $ops, WhiteLabelInterface $white_label, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEquals($result, $this->accessHandler->access($white_label, $op, $account), $this->whiteLabelAccessAssertMessage($op, $result, $white_label->language()
        ->getId()));
    }
  }

  /**
   * Asserts that node create access correctly grants or denies access.
   *
   * @param bool $result
   *   Whether access should be granted or not.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the node
   *   to check. If NULL, the untranslated (fallback) access is checked.
   */
  public function assertWhiteLabelCreateAccess($result, AccountInterface $account, $langcode = NULL) {
    $this->assertEquals($result, $this->accessHandler->createAccess(NULL, $account, [
      'langcode' => $langcode,
    ]), $this->whiteLabelAccessAssertMessage('create', $result, $langcode));
  }

  /**
   * Constructs an assert message to display which node access was tested.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the node
   *   to check. If NULL, the untranslated (fallback) access is checked.
   *
   * @return string
   *   An assert message string which contains information in plain English
   *   about the node access permission test that was performed.
   */
  public function whiteLabelAccessAssertMessage($operation, $result, $langcode = NULL) {
    return new FormattableMarkup(
      'White label access returns @result with operation %op, language code %langcode.',
      [
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty',
      ]
    );
  }

}
