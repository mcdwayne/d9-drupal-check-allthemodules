<?php

namespace Drupal\Tests\ingredient\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\ingredient\Entity\Ingredient;
use Drupal\ingredient\IngredientInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\UserCreationTrait;
use Drupal\user\RoleInterface;

/**
 * @coversDefaultClass \Drupal\ingredient\IngredientAccessControlHandler
 *
 * @group recipe
 */
class IngredientAccessTest extends KernelTestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
    createAdminRole as drupalCreateAdminRole;
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'ingredient',
    'system',
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
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('ingredient');
    $this->installConfig('ingredient');

    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('ingredient');

    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)
      ->set('permissions', [])
      ->save();

    // Create user 1 who has special permissions.
    $this->drupalCreateUser();
  }

  /**
   * Test Ingredient access by permission.
   */
  public function testIngredientAccess() {
    $ingredient = Ingredient::create(['name' => 'test name']);
    $ingredient->save();

    // Create a user with no permissions.
    $user1 = $this->drupalCreateUser();
    $this->assertIngredientCreateAccess(FALSE, $user1);
    $this->assertIngredientAccess([
      'view' => FALSE,
      'edit' => FALSE,
      'delete' => FALSE,
    ], $ingredient, $user1);

    // Create a user with all permissions.
    $user2 = $this->drupalCreateUser([
      'add ingredient',
      'view ingredient',
      'edit ingredient',
      'delete ingredient',
    ]);
    $this->assertIngredientCreateAccess(TRUE, $user2);
    $this->assertIngredientAccess([
      'view' => TRUE,
      'edit' => TRUE,
      'delete' => TRUE,
    ], $ingredient, $user2);
  }

  /**
   * Asserts that Ingredient access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected Ingredient access grants for the
   *   ingredient and account, with each key as the name of an operation (e.g.
   *   'view', 'delete') and each value a Boolean indicating whether access to
   *   that operation should be granted.
   * @param \Drupal\ingredient\IngredientInterface $ingredient
   *   The ingredient object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   */
  public function assertIngredientAccess(array $ops, IngredientInterface $ingredient, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEquals($result, $this->accessHandler->access($ingredient, $op, $account), $this->accessAssertMessage($op, $result, $ingredient->language()
        ->getId()));
    }
  }

  /**
   * Asserts that ingredient create access correctly grants or denies access.
   *
   * @param bool $result
   *   Whether access should be granted or not.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the
   *   ingredient to check. If NULL, the untranslated (fallback) access is
   *   checked.
   */
  public function assertIngredientCreateAccess($result, AccountInterface $account, $langcode = NULL) {
    $this->assertEquals($result, $this->accessHandler->createAccess(NULL, $account, [
      'langcode' => $langcode,
    ]), $this->accessAssertMessage('create', $result, $langcode));
  }

  /**
   * Constructs an assert message to display which ingredient access was tested.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the
   *   ingredient to check. If NULL, the untranslated (fallback) access is
   *   checked.
   *
   * @return string
   *   An assert message string which contains information in plain English
   *   about the ingredient access permission test that was performed.
   */
  public function accessAssertMessage($operation, $result, $langcode = NULL) {
    return new FormattableMarkup(
      'Ingredient access returns @result with operation %op, language code %langcode.',
      [
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty',
      ]
    );
  }

}