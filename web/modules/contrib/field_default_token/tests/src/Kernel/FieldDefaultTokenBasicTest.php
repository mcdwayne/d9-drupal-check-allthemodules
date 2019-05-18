<?php

namespace Drupal\Tests\field_default_token\Kernel;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests that tokens in field default values get replaced correctly.
 *
 * @group field_default_token
 */
class FieldDefaultTokenBasicTest extends FieldDefaultTokenKernelTestBase  {

  /**
   * The site name of the test site.
   *
   * @var string
   */
  protected $siteName;

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'entity_test';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $this->siteName = $config_factory->get('system.site')->get('name');
  }

  /**
   * Tests that the default value callback is registered for a new field.
   */
  public function testCallbackNewField() {
    $field = $this->createField();
    $field->setDefaultValue('This is the site name: [site:name]')->save();
    $this->assertEquals('field_default_token_default_value_callback', $field->getDefaultValueCallback());
  }

  /**
   * Tests that the default value callback is registered for an existing field.
   */
  public function testCallbackExistingField() {
    $field = $this->createField();
    $field->save();
    $this->assertEquals('', $field->getDefaultValueCallback());

    $field->setDefaultValue('This is the site name: [site:name]')->save();
    $this->assertEquals('field_default_token_default_value_callback', $field->getDefaultValueCallback());
  }

  /**
   * Tests the the default value callback is removed properly.
   */
  public function testCallbackRemoval() {
    $field = $this->createField();
    $field->setDefaultValue('This is the site name: [site:name]')->save();
    $this->assertEquals('field_default_token_default_value_callback', $field->getDefaultValueCallback());

    $field->setDefaultValue('There are no tokens to see here, move along')->save();
    $this->assertNull($field->getDefaultValueCallback());
  }

  /**
   * Test that tokens in a field default value get replaced properly.
   */
  public function testReplacement() {
    $field = $this->createField();
    $field->setDefaultValue('This is the site name: [site:name]')->save();

    $entity = EntityTest::create();
    $entity->save();

    $expected = [['value' => 'This is the site name: ' . $this->siteName]];
    $this->assertEquals($expected, $field->getDefaultValue($entity));
  }

}
