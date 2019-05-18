<?php

namespace Drupal\Tests\field_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests core entity types get the correct admin UI handlers set.
 *
 * @group entity_ui
 */
class AdminUIHandlerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'system',
    'user',
    // Needed for base fields on entities.
    'text',
    'node',
    'taxonomy',
    'field',
    'field_ui',
    'entity_ui',
  ];

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['field', 'node', 'user', 'taxonomy']);
  }

  /**
   * Tests the Entity UI admin handlers on entity types.
   */
  public function testEntityUIAdminHandlers() {
    $entity_type_manager = $this->container->get('entity_type.manager');

    $expected_handlers = [
      // Entity type ID => expected handler class.
      'node' => \Drupal\entity_ui\EntityHandler\BundleEntityCollection::class,
      'user' => \Drupal\entity_ui\EntityHandler\FieldUIWithoutBundleEntityProxy::class,
      'taxonomy_term' => \Drupal\entity_ui\EntityHandler\BundleEntityCollection::class,
    ];

    foreach ($expected_handlers as $entity_type_id => $handler_class) {
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);

      $this->assertTrue($entity_type->hasHandlerClass('entity_ui_admin'), "The $entity_type_id entity type has a handler set.");
      $this->assertEquals($entity_type->getHandlerClass('entity_ui_admin'), $handler_class, "The $entity_type_id entity type has the $handler_class handler set.");
    }

    // Check the user entity handler, which is a proxy, wraps the correct one.
    $user_entity_type = $entity_type_manager->getDefinition('user');
    $user_handler = $entity_type_manager->getHandler('user', 'entity_ui_admin');

    $route_prophecy = $this->prophesize(\Symfony\Component\Routing\Route ::CLASS);
    $route_prophecy->getPath()->willReturn('/admin/config/people/accounts');

    $route_collection_prophecy = $this->prophesize(\Symfony\Component\Routing\RouteCollection::CLASS);
    $route_collection_prophecy->get($user_entity_type->get('field_ui_base_route'))
      ->willReturn($route_prophecy->reveal());

    // We need to call this on the handler for it to set up the real wrapped
    // handler.
    $user_handler->getRoutes($route_collection_prophecy->reveal());

    // Hack into the proxy handler to get the real one.
    $reflection = new \ReflectionClass($user_handler);
    $property = $reflection->getProperty('realHandler');
    $property->setAccessible(TRUE);
    $real_user_handler = $property->getValue($user_handler);

    $this->assertEquals(\Drupal\entity_ui\EntityHandler\BasicFieldUI::class, get_class($real_user_handler));
  }

}
