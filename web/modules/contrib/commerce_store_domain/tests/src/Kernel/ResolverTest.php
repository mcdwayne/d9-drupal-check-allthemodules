<?php

namespace Drupal\Tests\commerce_store_domain\Kernel;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Domain resolver test.
 *
 * @group commerce_store_domain
 */
class ResolverTest extends CommerceKernelTestBase {

  public static $modules = [
    'commerce_store_domain',
  ];

  /**
   * Tests domain resolving.
   */
  public function testResolving() {
    $store = $this->createStore(NULL, NULL, 'online', FALSE);
    $store->get('domain')->appendItem('example.com');
    $store->save();

    $resolved_store = $this->container->get('commerce_store.current_store')->getStore();
    $this->assertNotEquals($store->uuid(), $resolved_store->uuid());

    $request = Request::create('/', 'GET', [], [], [], [
      'SERVER_NAME' => 'example.com',
      'HTTP_HOST' => 'example.com',
      'REMOTE_ADDR' => '203.0.113.1'
    ]);
    $this->assertEquals('example.com', $request->getHost());
    // Push the request to the request stack so `current_route_match` works.
    $this->container->get('request_stack')->push($request);

    $resolved_store = $this->container->get('commerce_store.current_store')->getStore();
    $this->assertEquals($store->uuid(), $resolved_store->uuid());

    $request = Request::create('/', 'GET', [], [], [], [
      'SERVER_NAME' => 'example.com',
      'HTTP_HOST' => 'example.com.au',
      'REMOTE_ADDR' => '203.0.113.1'
    ]);
    $this->assertEquals('example.com.au', $request->getHost());
    // Push the request to the request stack so `current_route_match` works.
    $this->container->get('request_stack')->push($request);

    $resolved_store = $this->container->get('commerce_store.current_store')->getStore();
    $this->assertNotEquals($store->uuid(), $resolved_store->uuid());

    $store->get('domain')->appendItem('example.com.au');
    $store->save();
    $store = $this->reloadEntity($store);

    // Push a new request, since the last .com.au was cached.
    $request = Request::create('/', 'GET', [], [], [], [
      'SERVER_NAME' => 'example.com',
      'HTTP_HOST' => 'example.com.au',
      'REMOTE_ADDR' => '203.0.113.1'
    ]);
    $this->assertEquals('example.com.au', $request->getHost());
    // Push the request to the request stack so `current_route_match` works.
    $this->container->get('request_stack')->push($request);

    $resolved_store = $this->container->get('commerce_store.current_store')->getStore();
    $this->assertNotEquals($this->store->uuid(), $resolved_store->uuid());
    $this->assertEquals($store->uuid(), $resolved_store->uuid());
  }

}
