<?php

/**
 * @file
 * Definition of CallbackTest.
 */

namespace Drupal\wow\Tests\Response;

use Drupal\wow\Mocks\ServiceStub;

use Drupal\wow\Mocks\ResponseStub;
use Drupal\wow\Mocks\ServiceStubRequest;
use Drupal\wow\Tests\UnitTestBase;

use WoW\Core\Response;
use WoW\Core\ResponseException;
use WoW\Core\ServiceInterface;
use WoW\Core\Callback\CallbackException;
use WoW\Core\Callback\CallbackReference;
use WoW\Core\Callback\CallbackUserFunc;

/**
 * Test Callback classes.
 */
class CallbackTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Callback',
      'description' => 'Unit Tests Callback.',
      'group' => 'WoW',
    );
  }

  public function testCallbackException() {
    // Test the exception callback.
    $callback = new CallbackException();

    try {
      $callback->process(new ServiceStub(), new ResponseStub());
      $this->fail('Exception not thrown.', 'WoW');
    }
    catch (ResponseException $exception) {
      $this->assertEqual(1, $exception->getCode(), 'Exception thrown HTTP code is 1.', 'WoW');
      $this->assertEqual("Stub.", $exception->getMessage(), 'Exception thrown message is "Stub.".', 'WoW');
    }
  }

  public function testCallbackReference() {
    // Test the reference callback.
    $reference = new \stdClass();
    $callback = new CallbackReference($reference);

    $this->assertEqual($reference, $callback->process(new ServiceStub(), new ResponseStub()), 'Reference is returned.', 'WoW');
  }

  public function testCallbackUserFunc() {
    // Test the user function callback.
    $parameters = array(1, 'string', new \stdClass(), array());
    $callback = new CallbackUserFunc(array($this, 'validateTestCallbackUserFunc'), $parameters);

    $this->assertEqual('callback called', $callback->process(new ServiceStub(), new ResponseStub()), 'UserFunc result is returned.', 'WoW');
  }

  public function validateTestCallbackUserFunc(ServiceInterface $service, Response $response, $int, $string, $object, array $array) {
    $parameters_valid = $service instanceof ServiceStub
      && $response instanceof ResponseStub
      && is_int($int)
      && is_string($string)
      && is_object($object)
      && is_array($array);
    $this->assertTrue($parameters_valid, 'UserFunc called with correct parameters.', 'WoW');

    return 'callback called';
  }
}
