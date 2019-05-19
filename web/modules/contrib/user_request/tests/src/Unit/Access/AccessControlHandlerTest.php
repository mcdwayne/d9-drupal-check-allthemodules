<?php

namespace Drupal\Tests\user_request\Unit\Access;

/**
 * Base class for access control handlers tests.
 *
 * @group user_request
 */
abstract class AccessControlHandlerTest extends AccessTest {

  /**
   * Access control handler under test.
   *
   * @var \Drupal\user_request\Access\RequestAccessControlHandler
   */
  protected $accessControlHandler;

  protected function createAccessControlHandler($class_name) {
    // Creates corresponding permission provider.
    $permission_provider = $this->getMockBuilder('\Drupal\entity\UncacheableEntityPermissionProvider')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_type = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $entity_type
      ->expects($this->any())
      ->method('hasHandlerClass')
      ->will($this->returnValue(TRUE));
    $entity_type
      ->expects($this->any())
      ->method('getHandlerClass')
      ->will($this->returnValue($permission_provider));

    // Creates access control handler.
    $access_handler = new $class_name($entity_type);
    $module_handler = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler
      ->expects($this->any())
      ->method('invokeAll')
      ->will($this->returnValue([]));
    $module_handler
      ->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValue([]));
    $access_handler->setModuleHandler($module_handler);
    return $access_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Resets all permissions.
    $this->permissions = [];
  }

}
