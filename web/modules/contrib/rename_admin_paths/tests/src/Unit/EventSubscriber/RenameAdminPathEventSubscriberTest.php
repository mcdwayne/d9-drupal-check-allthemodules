<?php

namespace Drupal\Tests\rename_admin_paths\Unit\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\rename_admin_paths\EventSubscriber\RenameAdminPathEventSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RenameAdminPathEventSubscriberTest extends UnitTestCase {

  public function testGetSubscribedEvents() {
    $events = RenameAdminPathEventSubscriber::getSubscribedEvents();

    $this->assertInternalType('array', $events);
    $this->assertCount(1, $events);
  }

  public function testDoNotRenamePaths() {
    $this->assertRoutePaths(
      [],
      [
        'home' => '/',
        'about' => '/about',
        'admin' => '/admin',
        'admin_slashed' => '/admin/',
        'admin_sub' => '/admin/sub',
        'admin_sub_sub' => '/admin/sub/sub',
        'admin_admin' => '/admin/admin',
        'admin_sub_admin' => '/admin/sub/admin',
        'admins' => '/admins',
        'admins_sub' => '/admins/sub',
        'user' => '/user',
        'user_slashed' => '/user/',
        'user_sub' => '/user/sub',
        'user_sub_sub' => '/user/sub/sub',
        'user_admin' => '/user/user',
        'user_sub_admin' => '/user/sub/user',
        'users' => '/users',
        'users_sub' => '/users/sub'
      ]
    );
  }

  public function testRenameAdminPath() {
    $this->assertRoutePaths(
      [
        'admin_path' => TRUE,
        'admin_path_value' => 'backend',
      ],
      [
        'home' => '/',
        'about' => '/about',
        'admin' => '/backend',
        'admin_slashed' => '/backend/',
        'admin_sub' => '/backend/sub',
        'admin_sub_sub' => '/backend/sub/sub',
        'admin_admin' => '/backend/admin',
        'admin_sub_admin' => '/backend/sub/admin',
        'admins' => '/admins',
        'admins_sub' => '/admins/sub',
        'user' => '/user',
        'user_slashed' => '/user/',
        'user_sub' => '/user/sub',
        'user_sub_sub' => '/user/sub/sub',
        'user_admin' => '/user/user',
        'user_sub_admin' => '/user/sub/user',
        'users' => '/users',
        'users_sub' => '/users/sub'
      ]
    );
  }

  public function testRenameUserPath() {
    $this->assertRoutePaths(
      [
        'user_path' => TRUE,
        'user_path_value' => 'member',
      ],
      [
        'home' => '/',
        'about' => '/about',
        'admin' => '/admin',
        'admin_slashed' => '/admin/',
        'admin_sub' => '/admin/sub',
        'admin_sub_sub' => '/admin/sub/sub',
        'admin_admin' => '/admin/admin',
        'admin_sub_admin' => '/admin/sub/admin',
        'admins' => '/admins',
        'admins_sub' => '/admins/sub',
        'user' => '/member',
        'user_slashed' => '/member/',
        'user_sub' => '/member/sub',
        'user_sub_sub' => '/member/sub/sub',
        'user_admin' => '/member/user',
        'user_sub_admin' => '/member/sub/user',
        'users' => '/users'
      ]
    );
  }

  public function testRenameAdminPaths() {
    $this->assertRoutePaths(
      [
        'admin_path' => TRUE,
        'admin_path_value' => 'backend',
        'user_path' => TRUE,
        'user_path_value' => 'member',
      ],
      [
        'home' => '/',
        'about' => '/about',
        'admin' => '/backend',
        'admin_slashed' => '/backend/',
        'admin_sub' => '/backend/sub',
        'admin_sub_sub' => '/backend/sub/sub',
        'admin_admin' => '/backend/admin',
        'admin_sub_admin' => '/backend/sub/admin',
        'admins' => '/admins',
        'admins_sub' => '/admins/sub',
        'user' => '/member',
        'user_slashed' => '/member/',
        'user_sub' => '/member/sub',
        'user_sub_sub' => '/member/sub/sub',
        'user_admin' => '/member/user',
        'user_sub_admin' => '/member/sub/user',
        'users' => '/users',
        'users_sub' => '/users/sub'
      ]
    );
  }

  /**
   * @param array $config
   * @param array $routes
   */
  private function assertRoutePaths(array $config, array $routes) {
    $route_collection = $this->getRouteCollection();

    $event_subscriber = new RenameAdminPathEventSubscriber(
      $this->getConfigFactoryStub(
        [
          'rename_admin_paths.settings' => $config,
        ]
      )
    );
    $event_subscriber->onRoutesAlter(new RouteBuildEvent($route_collection));

    foreach ($routes as $name => $path) {
      $this->assertEquals($path, $route_collection->get($name)->getPath());
    }
  }

  /**
   * @return \Symfony\Component\Routing\RouteCollection
   */
  private function getRouteCollection() {
    $route_collection = new RouteCollection();
    foreach ([
               'home' => '/',
               'about' => '/about',
               'admin' => '/admin',
               'admin_slashed' => '/admin/',
               'admin_sub' => '/admin/sub',
               'admin_sub_sub' => '/admin/sub/sub',
               'admin_admin' => '/admin/admin',
               'admin_sub_admin' => '/admin/sub/admin',
               'admins' => '/admins',
               'admins_sub' => '/admins/sub',
               'user' => '/user',
               'user_slashed' => '/user/',
               'user_sub' => '/user/sub',
               'user_sub_sub' => '/user/sub/sub',
               'user_admin' => '/user/user',
               'user_sub_admin' => '/user/sub/user',
               'users' => '/users',
               'users_sub' => '/users/sub',
             ] as $name => $path) {
      $route_collection->add($name, new Route($path));
    }

    return $route_collection;
  }
}
