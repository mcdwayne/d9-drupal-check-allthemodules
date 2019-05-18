<?php

namespace Drupal\Tests\bibcite_entity\Kernel;

use Drupal\bibcite_entity\Entity\Reference;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests reference field level access.
 *
 * @group reference
 */
class ReferenceFieldAccessTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['bibcite', 'bibcite_entity'];

  /**
   * Fields that only users with administer nodes permissions can change.
   *
   * @var array
   */
  protected $administrativeFields = ['uid', 'created'];

  /**
   * These fields are automatically managed and can not be changed by any user.
   *
   * @var array
   */
  protected $readOnlyFields = ['changed'];

  /**
   * Test permissions on references status field.
   */
  public function testAccessToAdministrativeFields() {

    // An administrator user. No user exists yet, ensure that the first user
    // does not have UID 1.
    $content_admin_user = $this->createUser(['uid' => 2], ['administer bibcite_reference']);

    // Two different editor users.
    $page_creator_user = $this->createUser([], ['create book bibcite_reference', 'edit own book bibcite_reference', 'delete own book bibcite_reference']);
    $page_manager_user = $this->createUser([], ['create book bibcite_reference', 'edit any book bibcite_reference', 'delete any book bibcite_reference']);

    // An unprivileged user.
    $page_unrelated_user = $this->createUser([], ['view bibcite_reference']);

    // List of all users.
    $test_users = [
      $content_admin_user,
      $page_creator_user,
      $page_manager_user,
      $page_unrelated_user,
    ];

    // Create two book references. One is owned by our test-user
    // "page_creator" and one by "page_manager".
    $reference1 = Reference::create([
      'title' => $this->randomMachineName(8),
      'uid' => $page_creator_user->id(),
      'type' => 'page',
    ]);
    $reference2 = Reference::create([
      'title' => $this->randomMachineName(8),
      'uid' => $page_manager_user->id(),
      'type' => 'article',
    ]);

    foreach ($this->administrativeFields as $field) {

      // Checks on view operations.
      foreach ($test_users as $account) {
        $may_view = $reference1->{$field}->access('view', $account);
        $this->assertTrue($may_view, new FormattableMarkup('Any user may view the field @name.', ['@name' => $field]));
      }

      // Checks on edit operations.
      $may_update = $reference1->{$field}->access('edit', $page_creator_user);
      $this->assertFalse($may_update, new FormattableMarkup('Users with permission "edit own book bibcite_reference" is not allowed to the field @name.', ['@name' => $field]));
      $may_update = $reference2->{$field}->access('edit', $page_creator_user);
      $this->assertFalse($may_update, new FormattableMarkup('Users with permission "edit own book bibcite_reference" is not allowed to the field @name.', ['@name' => $field]));
      $may_update = $reference2->{$field}->access('edit', $page_manager_user);
      $this->assertFalse($may_update, new FormattableMarkup('Users with permission "edit any book bibcite_reference" is not allowed to the field @name.', ['@name' => $field]));
      $may_update = $reference1->{$field}->access('edit', $page_manager_user);
      $this->assertFalse($may_update, new FormattableMarkup('Users with permission "edit any book bibcite_reference" is not allowed to the field @name.', ['@name' => $field]));
      $may_update = $reference2->{$field}->access('edit', $page_unrelated_user);
      $this->assertFalse($may_update, new FormattableMarkup('Users not having permission "edit any book bibcite_reference" is not allowed to the field @name.', ['@name' => $field]));
      $may_update = $reference1->{$field}->access('edit', $content_admin_user);
      $this->assertTrue($may_update, new FormattableMarkup('Users with permission "administer bibcite_reference" may edit @name fields on all references.', ['@name' => $field]));
    }

    foreach ($this->readOnlyFields as $field) {
      // Check view operation.
      foreach ($test_users as $account) {
        $may_view = $reference1->{$field}->access('view', $account);
        $this->assertTrue($may_view, new FormattableMarkup('Any user may view the field @name.', ['@name' => $field]));
      }

      // Check edit operation.
      foreach ($test_users as $account) {
        $may_view = $reference1->{$field}->access('edit', $account);
        $this->assertFalse($may_view, new FormattableMarkup('No user is not allowed to edit the field @name.', ['@name' => $field]));
      }
    }
  }

}
