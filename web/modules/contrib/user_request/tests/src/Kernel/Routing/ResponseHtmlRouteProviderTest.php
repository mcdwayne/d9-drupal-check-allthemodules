<?php

namespace Drupal\Tests\user_request\Kernel\Routing;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user_request\Routing\ResponseHtmlRouteProvider;

/**
 * @coversDefaultClass \Drupal\user_request\Routing\ResponseHtmlRouteProvider
 * @group user_request
 */
class ResponseHtmlRouteProviderTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user_request'];

  /**
   * The route provider under test.
   *
   * @var \Drupal\user_request\Routing\ResponseHtmlRouteProvider
   */
  protected $routeProvider;

  /**
   * The request entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  public function testGetAddFormRoute() {
    $routes = $this->routeProvider->getRoutes($this->entityType);
    $add_form_route = $routes->get('entity.user_request_response.add_form');
    $parameters = $add_form_route->getOption('parameters');
    $requirements = $add_form_route->getRequirements();

    $this->assertEquals([
      'user_request' => [
        'type' => 'entity:user_request',
      ],
    ], $parameters);
    $this->assertEquals([
      '_entity_access' => 'user_request.update',
      '_user_request_response_form_access' => 'TRUE',
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
    $this->routeProvider = new ResponseHtmlRouteProvider($entity_type_manager, 
      $entity_field_manager);

    // Gets the entity type.
    $this->entityType = $entity_type_manager->getDefinition('user_request_response');
  }

}
