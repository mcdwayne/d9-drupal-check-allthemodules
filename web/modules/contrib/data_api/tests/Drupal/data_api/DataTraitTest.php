<?php

namespace Drupal\data_api;

class DataTraitTest extends \PHPUnit_Framework_TestCase {

  /**
   * Provides data for testAssertSameObjectForMultipleOfSameEntityType.
   */
  function DataForTestAssertSameObjectForMultipleOfSameEntityTypeProvider() {
    $tests = array();
    $tests[] = array('g', NULL);
    $tests[] = array('n', 'node');
    $tests[] = array('u', 'user');

    return $tests;
  }

  /**
   * @dataProvider DataForTestAssertSameObjectForMultipleOfSameEntityTypeProvider
   */
  public function testAssertSameObjectForMultipleOfSameEntityTypeReturnsSameInstance($key, $entity_type) {
    $reflector = new \ReflectionClass(get_class($this->obj));
    $method = $reflector->getMethod('getDataApiData');
    $method->setAccessible('public');
    $$key = $method->invokeArgs($this->obj, array($entity_type));
    $repeat{$key} = $method->invokeArgs($this->obj, array($entity_type));
    $vars = $this->obj->vars();
    $this->assertSame($vars[$key], $$key);
    $this->assertSame($$key, $repeat{$key});
  }

  public function testEntityTypes() {
    $vars = $this->obj->vars();
    $this->assertNull($vars['g']->getEntityType());
    $this->assertSame('node', $vars['n']->getEntityType());
    $this->assertSame('user', $vars['u']->getEntityType());
  }

  public function testValues() {
    $vars = $this->obj->vars();
    $this->assertInstanceOf(get_class($this->dataApiData), $vars['g']);
    $this->assertNull($vars['e']);
    $this->assertInstanceOf(get_class($this->dataApiData), $vars['n']);
    $this->assertInstanceOf(get_class($this->dataApiData), $vars['u']);
  }

  public function testAreNotTheSame() {
    $vars = $this->obj->vars();
    $this->assertNotSame($vars['g'], $vars['e']);
    $this->assertNotSame($vars['e'], $vars['n']);
    $this->assertNotSame($vars['n'], $vars['u']);
  }

  public function testAreSetInConstructor() {
    $vars = $this->obj->vars();
    $this->assertArrayHasKey('g', $vars);
    $this->assertArrayHasKey('e', $vars);
    $this->assertArrayHasKey('n', $vars);
    $this->assertArrayHasKey('u', $vars);
  }

  public function setUp() {
    $this->dataApiData = new DataMock;
    $this->obj = new TestDataTrait($this->dataApiData);
  }
}

/**
 * Class TestDataTrait
 *
 * Used to test the Trait in the tests above.
 *
 * @package Drupal\data_api
 */
class TestDataTrait {

  use DataTrait;

  /**
   * TestDataTrait constructor.
   */
  public function __construct(Data $dataApiData) {
    $this->setDataApiData($dataApiData);
  }

  public function vars() {
    return get_object_vars($this);
  }
}
