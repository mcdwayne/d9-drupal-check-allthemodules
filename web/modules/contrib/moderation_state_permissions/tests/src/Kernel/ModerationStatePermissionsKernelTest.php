<?php

namespace Drupal\Tests\moderation_state_permissions\Kernel;

use Drupal\moderation_state_permissions\PermissionsGenerator;
use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests Workflow type's required states and configuration initialization.
 *
 * @coversDefaultClass \Drupal\moderation_state_permissions\PermissionsGenerator
 *
 * @group workflows
 */
class ModerationStatePermissionsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workflows', 'workflow_type_test'];

  /**
   * @covers ::getPermissions
   * @covers ::getPermissionName
   */
  public function testPermissionGeneration() {
    $workflow = new Workflow([
      'id' => 'test',
      'type' => 'workflow_type_required_state_test',
    ], 'workflow');
    $workflow->save();

    $permissionsGenerator = new PermissionsGenerator();
    $permissionsGenerator->getPermissions();

    // TODO: Add test coverage.
  }

}
