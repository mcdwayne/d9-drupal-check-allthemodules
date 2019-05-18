<?php

namespace Drupal\Tests\bibcite_entity\Kernel;

use Drupal\bibcite_entity\Entity\Reference;
use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests basic reference_access functionality.
 *
 * @group reference
 */
class ReferenceAccessTest extends KernelTestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
    createAdminRole as drupalCreateAdminRole;
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'bibcite',
    'bibcite_entity',
    'serialization',
    'user',
    'system',
    'filter',
    'field',
    'text',
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
    $this->installEntitySchema('bibcite_reference_type');
    $this->installEntitySchema('bibcite_reference');
    $this->installConfig('filter');
    $this->installConfig('bibcite_entity');
    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('bibcite_reference');
    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)
      ->set('permissions', [])
      ->save();

    // Create user 1 who has special permissions.
    $this->drupalCreateUser();
  }

  /**
   * Runs basic tests for reference_access function.
   */
  public function testReferenceAccess() {
    // Ensures user without any reference permission can do nothing.
    $web_user1 = $this->drupalCreateUser([]);
    $reference1 = $this->createReference();
    $this->assertReferenceCreateAccess($reference1->bundle(), FALSE, $web_user1);
    $this->assertReferenceAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $reference1, $web_user1);

    // Ensures user with bibcite_reference action permissions can do everything.
    $web_user2 = $this->drupalCreateUser([
      'create bibcite_reference',
      'view bibcite_reference',
      'edit any bibcite_reference',
      'delete any bibcite_reference',
    ]);
    $this->assertReferenceCreateAccess($reference1->bundle(), TRUE, $web_user2);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $reference1, $web_user2);

    $web_user3 = $this->drupalCreateUser([
      'view bibcite_reference',
    ]);
    // User cannot create reference without permission.
    $this->assertReferenceCreateAccess($reference1->bundle(), FALSE, $web_user3);

    // User can 'edit/delete own reference', but another user cannot.
    $web_user4 = $this->drupalCreateUser([
      'create bibcite_reference',
      'view bibcite_reference',
      'edit own bibcite_reference',
      'delete own bibcite_reference',
    ]);
    $web_user5 = $this->drupalCreateUser([
      'view bibcite_reference',
      'edit own bibcite_reference',
      'delete own bibcite_reference',
    ]);
    $reference2 = $this->createReference([
      'uid' => $web_user4->id(),
    ]);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $reference2, $web_user4);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $reference2, $web_user5);

    // User should not be able to edit or delete references they do not own.
    $reference3 = $this->createReference([]);
    $web_user6 = $this->drupalCreateUser([
      'create bibcite_reference',
      'view bibcite_reference',
      'edit own bibcite_reference',
      'delete own bibcite_reference',
    ]);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $reference3, $web_user6);

    // User can 'edit/delete own reference',
    // another user can 'edit/delete any reference'.
    $web_user7 = $this->drupalCreateUser([
      'create bibcite_reference',
      'view bibcite_reference',
      'edit own bibcite_reference',
      'delete own bibcite_reference',
    ]);
    $web_user8 = $this->drupalCreateUser([
      'view bibcite_reference',
      'edit any bibcite_reference',
      'delete any bibcite_reference',
    ]);
    $reference4 = $this->createReference([
      'uid' => $web_user7->id(),
    ]);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $reference4, $web_user7);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $reference4, $web_user8);

    // Test create/edit/delete BUNDLE.
    $web_user9 = $this->drupalCreateUser([
      'create miscellaneous bibcite_reference',
      'view bibcite_reference',
      'edit any book bibcite_reference',
      'delete any book bibcite_reference',
    ]);
    $this->assertReferenceCreateAccess('miscellaneous', TRUE, $web_user9);
    $this->assertReferenceCreateAccess('book', FALSE, $web_user9);
    $web_user10 = $this->drupalCreateUser([
      'create book bibcite_reference',
      'view bibcite_reference',
      'edit any miscellaneous bibcite_reference',
      'delete any miscellaneous bibcite_reference',
    ]);
    $this->assertReferenceCreateAccess('miscellaneous', FALSE, $web_user10);
    $this->assertReferenceCreateAccess('book', TRUE, $web_user10);
    $reference5 = $this->createReference([
      'type' => 'miscellaneous',
      'uid' => $web_user9->id(),
    ]);
    $reference6 = $this->createReference([
      'type' => 'book',
      'uid' => $web_user10->id(),
    ]);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $reference5, $web_user9);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $reference5, $web_user10);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $reference6, $web_user9);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $reference6, $web_user10);


    $web_user11 = $this->drupalCreateUser([
      'create book bibcite_reference',
      'view bibcite_reference',
      'edit own book bibcite_reference',
      'delete own book bibcite_reference',
    ]);
    $this->assertReferenceCreateAccess('miscellaneous', FALSE, $web_user11);
    $this->assertReferenceCreateAccess('book', TRUE, $web_user11);
    $web_user12 = $this->drupalCreateUser([
      'create book bibcite_reference',
      'view bibcite_reference',
      'edit own book bibcite_reference',
      'delete own book bibcite_reference',
    ]);
    $this->assertReferenceCreateAccess('miscellaneous', FALSE, $web_user12);
    $this->assertReferenceCreateAccess('book', TRUE, $web_user12);
    $reference7 = $this->createReference([
      'type' => 'book',
      'uid' => $web_user11->id(),
    ]);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $reference7, $web_user11);
    $this->assertReferenceAccess([
      'view' => TRUE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $reference7, $web_user12);
  }

  /**
   * Test operations not supported by reference grants.
   */
  public function testUnsupportedOperation() {
    $web_user = $this->drupalCreateUser(['view bibcite_reference']);
    $reference = $this->createReference();
    $this->assertReferenceAccess(['random_operation' => FALSE], $reference, $web_user);
  }

  /**
   * Asserts that reference access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected reference access grants for the
   *   reference and account, with each key as the name of an operation
   *   (e.g. 'view', 'delete') and each value a Boolean indicating whether
   *   access to that operation should be granted.
   * @param \Drupal\bibcite_entity\Entity\ReferenceInterface $reference
   *   The reference object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   */
  public function assertReferenceAccess(array $ops, ReferenceInterface $reference, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEquals($result, $this->accessHandler->access($reference, $op, $account), $this->referenceAccessAssertMessage($op, $result, $reference->language()
        ->getId()));
    }
  }

  /**
   * Asserts that reference create access correctly grants or denies access.
   *
   * @param string $bundle
   *   The reference bundle to check access to.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the reference
   *   to check. If NULL, the untranslated (fallback) access is checked.
   */
  public function assertReferenceCreateAccess($bundle, $result, AccountInterface $account, $langcode = NULL) {
    $this->assertEquals($result, $this->accessHandler->createAccess($bundle, $account, [
      'langcode' => $langcode,
    ]), $this->referenceAccessAssertMessage('create', $result, $langcode));
  }

  /**
   * Constructs an assert message to display which reference access was tested.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the reference
   *   to check. If NULL, the untranslated (fallback) access is checked.
   *
   * @return string
   *   An assert message string which contains information in plain English
   *   about the reference access permission test that was performed.
   */
  public function referenceAccessAssertMessage($operation, $result, $langcode = NULL) {
    return new FormattableMarkup(
      'Reference access returns @result with operation %op, language code %langcode.',
      [
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty',
      ]
    );
  }

  /**
   * Creates a reference based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the node, as used in
   *   entity_create(). Override the defaults by specifying the key and value
   *   in the array.
   *
   * @return \Drupal\bibcite_entity\Entity\ReferenceInterface
   *   The created reference entity.
   */
  private function createReference(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'title' => $this->randomMachineName(8),
      'type' => 'miscellaneous',
      'uid' => \Drupal::currentUser()->id(),
    ];
    $reference = Reference::create($settings);
    $reference->save();

    return $reference;
  }

}
