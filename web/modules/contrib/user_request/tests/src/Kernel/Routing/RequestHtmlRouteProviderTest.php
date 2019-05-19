<?php

namespace Drupal\Tests\user_request\Kernel\Routing;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user_request\Routing\RequestHtmlRouteProvider;

/**
 * @coversDefaultClass \Drupal\user_request\Routing\RequestHtmlRouteProvider
 * @group user_request
 */
class RequestHtmlRouteProviderTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user_request'];

  /**
   * The route provider under test.
   *
   * @var \Drupal\user_request\Routing\RequestHtmlRouteProvider
   */
  protected $routeProvider;

  /**
   * The request entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  public function testGetEditFormRoute() {
    $routes = $this->routeProvider->getRoutes($this->entityType);
    $edit_form_route = $routes->get('entity.user_request.edit_form');
    $requirements = $edit_form_route->getRequirements();

    $this->assertEquals([
      '_user_request_edit_form_access' => 'TRUE',
    ], $requirements);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates route provider to test.
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $this->routeProvider = new RequestHtmlRouteProvider($entity_type_manager, 
      $entity_field_manager);

    // Gets the entity type.
    $this->entityType = $entity_type_manager->getDefinition('user_request');
  }

}
