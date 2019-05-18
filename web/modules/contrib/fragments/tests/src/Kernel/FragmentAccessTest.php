<?php

namespace Drupal\Tests\fragments\Kernel;

use Drupal\fragments\Entity\FragmentInterface;
use Drupal\user\RoleInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\fragments\Traits\FragmentCreationTrait;
use Drupal\Tests\fragments\Traits\FragmentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests fragment permissions.
 *
 * @group fragments
 */
class FragmentAccessTest extends KernelTestBase {

  use FragmentCreationTrait;
  use FragmentTypeCreationTrait;
  use UserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
    createAdminRole as drupalCreateAdminRole;
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'fragments',
    'datetime',
    'field',
    'filter',
    'system',
    'text',
    'user',
  ];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('fragment');
    $this->installConfig('filter');
    $this->installConfig('fragments');
    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('fragment');
    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)
      ->set('permissions', [])
      ->save();

    // Create user 1 who has special permissions.
    $this->drupalCreateUser();

    // Create a fragment type.
    $this->createFragmentType([
      'id' => 'simple_text',
      'name' => 'Simple text',
    ]);
  }

  /**
   * Runs basic tests for fragment access.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures saving the entity an exception is thrown.
   */
  public function testFragmentAccess() {
    // Ensures user with 'administer fragments entities' permission can do
    // everything.
    $web_user2 = $this->drupalCreateUser(['administer fragment entities']);
    $fragment2 = $this->createFragment(['type' => 'simple_text']);
    $this->assertFragmentCreateAccess($fragment2->bundle(), TRUE, $web_user2);
    $this->assertFragmentAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
      'view individual' => TRUE,
    ], $fragment2, $web_user2);

    // User cannot create content without permission.
    $web_user3 = $this->drupalCreateUser(['view simple_text fragments']);
    $fragment3 = $this->createFragment([
      'status' => 0,
      'user_id' => $web_user3->id(),
      'type' => 'simple_text',
    ]);
    $this->assertFragmentCreateAccess($fragment3->bundle(), FALSE, $web_user3);

    // User can view own fragments, but another user cannot.
    $web_user4 = $this->drupalCreateUser(['update own simple_text fragments']);
    $web_user5 = $this->drupalCreateUser(['update own simple_text fragments']);
    $fragment4 = $this->createFragment([
      'status' => 0,
      'user_id' => $web_user4->id(),
      'type' => 'simple_text',
    ]);
    $this->assertFragmentAccess([
      'view' => TRUE,
      'update' => TRUE,
    ], $fragment4, $web_user4);
    $this->assertFragmentAccess(['view' => FALSE], $fragment4, $web_user5);

    // Tests the default access provided for a published fragment.
    $fragment5 = $this->createFragment(['type' => 'simple_text']);
    $this->assertFragmentAccess([
      'view' => TRUE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $fragment5, $web_user3);

    // Tests the "update BUNDLE fragments" and "delete BUNDLE fragments"
    // permissions.
    $web_user6 = $this->drupalCreateUser([
      'update simple_text fragments',
      'delete simple_text fragments',
    ]);
    $fragment6 = $this->createFragment(['type' => 'simple_text']);
    $this->assertFragmentAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $fragment6, $web_user6);

    // Tests the "update own BUNDLE fragments" and "delete own BUNDLE fragments"
    // permission.
    $web_user7 = $this->drupalCreateUser([
      'update own simple_text fragments',
      'delete own simple_text fragments',
    ]);
    // User should not be able to edit or delete fragments they do not own.
    $this->assertFragmentAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $fragment6, $web_user7);

    // User should be able to edit or delete fragments they own.
    $fragment7 = $this->createFragment([
      'type' => 'simple_text',
      'user_id' => $web_user7->id(),
    ]);
    $this->assertFragmentAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $fragment7, $web_user7);
  }

  /**
   * Test operations not supported by fragment grants.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures saving the entity an exception is thrown.
   */
  public function testUnsupportedOperation() {
    $web_user = $this->drupalCreateUser(['access content']);
    $fragment = $this->createFragment(['type' => 'simple_text']);
    $this->assertFragmentAccess(['random_operation' => FALSE], $fragment, $web_user);
  }

  /**
   * Asserts that fragment access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected fragment access grants for the
   *   fragment and account, with each key as the name of an operation (e.g.
   *   'view', 'delete') and each value a Boolean indicating whether access to
   *   that operation should be granted.
   * @param \Drupal\fragments\Entity\FragmentInterface $fragment
   *   The fragment object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   */
  public function assertFragmentAccess(array $ops, FragmentInterface $fragment, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEquals($result, $this->accessHandler->access($fragment, $op, $account), $this->fragmentAccessAssertMessage($op, $result, $fragment->language()
        ->getId()));
    }
  }

  /**
   * Asserts that fragment create access correctly grants or denies access.
   *
   * @param string $bundle
   *   The fragment bundle to check access to.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the fragment
   *   to check. If NULL, the untranslated (fallback) access is checked.
   */
  public function assertFragmentCreateAccess($bundle, $result, AccountInterface $account, $langcode = NULL) {
    $this->assertEquals($result, $this->accessHandler->createAccess($bundle, $account, [
      'langcode' => $langcode,
    ]), $this->fragmentAccessAssertMessage('create', $result, $langcode));
  }

  /**
   * Constructs an assert message to display which fragment access was tested.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the fragment
   *   to check. If NULL, the untranslated (fallback) access is checked.
   *
   * @return string
   *   An assert message string which contains information in plain English
   *   about the fragment access permission test that was performed.
   */
  public function fragmentAccessAssertMessage($operation, $result, $langcode = NULL) {
    return new FormattableMarkup(
      'Fragment access returns @result with operation %op, language code %langcode.',
      [
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty',
      ]
    );
  }

}
