<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/15/17
 * Time: 12:16 PM
 */

namespace Drupal\Tests\Unit\basicshib;


use Drupal\basicshib\AttributeMapper;
use Drupal\basicshib\Exception\AttributeException;
use Drupal\Tests\basicshib\Traits\ConfigurationFactoryCreatorTrait;
use Drupal\Tests\basicshib\Traits\MockTrait;
use Drupal\Tests\UnitTestCase;

class AttributeMapperTest extends UnitTestCase {
  use MockTrait;

  /**
   * Test getting an attribute under valid circumstances.
   */
  public function testGetAttribute() {
    $value = $this->randomMachineName();

    $config_factory = $this->getMockConfigFactory();
    $request_stack = $this->getMockRequestStack([
      'Shib_Session_ID' => $value,
    ]);

    $mapper = new AttributeMapper($config_factory, $request_stack);

    $this->assertEquals($value, $mapper->getAttribute('session_id'));
  }

  /**
   * Test attempting to get an unmapped attribute.
   */
  public function testGetAttributeUnmapped() {
    $config_factory = $this->getMockConfigFactory([
      'basicshib.settings' => ['attribute_map' => ['key' => [], 'optional' => []]],
    ]);

    $request_stack = $this->getMockRequestStack();

    $mapper = new AttributeMapper($config_factory, $request_stack);

    try {
      $mapper->getAttribute('session_id');
    }
    catch (AttributeException $exception) {
      $this->assertEquals(AttributeException::NOT_MAPPED, $exception->getCode());
    }

    $this->assertNotFalse(isset($exception));

  }

  /**
   * Test getting an attribute when unset and empty is not allowed.
   */
  public function testGetAttributeUnsetAndEmptyNotAllowed() {
    $config_factory = $this->getMockConfigFactory();

    $request_stack = $this->getMockRequestStack([
      'Shib_Session_ID' => '',
    ]);

    $mapper = new AttributeMapper($config_factory, $request_stack);

    try {
      $mapper->getAttribute('session_id', false);
    }
    catch (AttributeException $exception) {
      $this->assertEquals(AttributeException::NOT_SET, $exception->getCode());
    }

    $this->assertNotFalse(isset($exception));
  }

  /**
   * Test getting an attribute when unset and empty is not allowed.
   */
  public function testGetAttributeUnsetAndEmptyAllowed() {
    $config_factory = $this->getMockConfigFactory();

    $request_stack = $this->getMockRequestStack([
      'Shib_Session_ID' => '',
    ]);

    $mapper = new AttributeMapper($config_factory, $request_stack);

    try {
      $mapper->getAttribute('session_id', true);
    }
    catch (AttributeException $exception) {}

    $this->assertFalse(isset($exception));
  }

  public function testGetOptionalAttribute() {
    $config_factory = $this->getMockConfigFactory();

    $request_stack = $this->getMockRequestStack([
      'OPT1' => 'ok',
    ]);

    $mapper = new AttributeMapper($config_factory, $request_stack);

    $this->assertEquals('ok', $mapper->getAttribute('opt1', true));
  }

  /**
   * Assert that an optional attribute that duplicates another attribute throws
   * an exception.
   */
  public function testDuplicatingAttributeThrowsException() {
    $config_factory = $this->getMockConfigFactory([
      'basicshib.settings' => [
        'attribute_map' => [
          'key' => [
            'session_id' => 'Shib_Session_ID',
          ],
          'optional' => [
             ['id' => 'session_id', 'name' => 'other'],
          ],
        ],
      ],
    ]);

    $request_stack = $this->getMockRequestStack([
      'Shib_Session_ID' => '',
    ]);


    try {
      new AttributeMapper($config_factory, $request_stack);
      $this->fail('An exception was expected');
    }
    catch (AttributeException $exception) {
      $this->assertEquals(AttributeException::DUPLICATE_ID, $exception->getCode());
    }
  }
}
