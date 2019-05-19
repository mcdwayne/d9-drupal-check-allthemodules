<?php

/**
 * @file
 * Definition of RequestTest.
 */

namespace Drupal\wow\Tests;

use Drupal\wow\Mocks\ServiceMockRequest;

use WoW\Core\Request;

/**
 * Test Request class.
 */
class RequestTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Request',
      'description' => 'Unit Tests Request.',
      'group' => 'WoW',
    );
  }

  public function testRequestQuery() {
    $service = new ServiceMockRequest();

    // Test a query string.
    $request = new Request($service, 'dummy');
    $request->setQuery('fields', 'test')
            ->execute();
    $this->assertEqual('test', $service->query['fields'], 'Found test fields parameter on request.', 'WoW');

    // Test a query array.
    $request = new Request($service, 'dummy');
    $request->setQuery('fields', array('field1', 'field2', 'field3'))
            ->execute();
    $this->assertEqual('field1,field2,field3', $service->query['fields'], 'Found field1,field2,field3 fields parameter on request.', 'WoW');
  }

  public function testLocaleQuery() {
    $service = new ServiceMockRequest();

    // Test an available locale.
    $request = new Request($service, 'dummy');
    $request->setLocale('test')
            ->execute();
    $this->assertEqual('Test Locale', $service->query['locale'], 'Found locale parameter on request.', 'WoW');

    // Test a non-supported locale.
    $request = new Request($service, 'dummy');
    $request->setLocale('non-supported')
            ->execute();
    $this->assertFalse(isset($service->query['locale']), 'Locale parameter not on request.', 'WoW');
  }

  public function testIfModifiedSinceHeader() {
    $service = new ServiceMockRequest();

    // Test the date format.
    $request = new Request($service, 'dummy');
    $request->setIfModifiedSince(543210)
            ->execute();
    $this->assertEqual('Wed, 07 Jan 1970 06:53:30 GMT', $service->headers['If-Modified-Since'], 'Found If-Modified-Since header on request.', 'WoW');
  }

}
