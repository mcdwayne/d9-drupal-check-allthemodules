<?php

/**
 * @file
 * Definition of HandlerTest.
 */

namespace Drupal\wow\Tests\Response;

use Drupal\wow\Mocks\CallbackMock;
use Drupal\wow\Mocks\RequestMock;
use Drupal\wow\Mocks\ResponseStub;
use Drupal\wow\Mocks\ServiceStub;
use Drupal\wow\Mocks\ServiceStubRequest;
use Drupal\wow\Tests\UnitTestBase;

use WoW\Core\Request;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;
use WoW\Core\Callback\CallbackInterface;
use WoW\Core\Handler\Handler;
use WoW\Core\Service\Service;

/**
 * Test Handler class.
 */
class HandlerTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Handler',
      'description' => 'Unit Tests Handler.',
      'group' => 'WoW',
    );
  }

  public function testHandler() {
    // Configure the test to handle an HTTP response 200.
    $request = new RequestMock(new ResponseStub(200));
    $callback_ok = new CallbackMock();
    $callback_nok = new CallbackMock();
    $callback_default = new CallbackMock();

    // Adds a set of callback object.
    $handler = new Handler(new ServiceStub(), $request);
    $handler->mapCallback(0, $callback_default);
    $handler->mapCallback(200, $callback_ok);
    $handler->mapCallback(500, $callback_nok);

    // Executes the request.
    $response = $handler->execute();

    // Asserts the mocked object.
    $this->assertTrue($callback_ok->processedCalled(), 'The HTTP Code 200 callback has been processed.', 'WoW');
    $this->assertTrue($response instanceof ResponseStub, 'The return value is a Response.', 'WoW');
    $this->assertFalse($callback_nok->processedCalled(), 'The HTTP Code 500 callback has not been processed.', 'WoW');
    $this->assertFalse($callback_default->processedCalled(), 'The default callback has not been processed.', 'WoW');
  }

  public function testDefaultHandler() {
    // Configure the test to handle an HTTP response 200.
    $request = new RequestMock(new ResponseStub(200));
    $callback_default = new CallbackMock();
    $callback_default->return = 'test';

    // Adds a default callback object.
    $handler = new Handler(new ServiceStub(), $request);
    $handler->mapCallback(0, $callback_default);
    $this->assertFalse($request->executedCalled(), 'Request has not been executed.', 'WoW');

    // Executes the request.
    $response = $handler->execute();

    // Asserts the mocked object.
    $this->assertTrue($request->executedCalled(), 'Request has been executed.', 'WoW');
    $this->assertTrue($callback_default->processedCalled(), 'The default callback has been processed.', 'WoW');
    $this->assertEqual('test', $response, 'The return value is a test string.', 'WoW');
  }

}
