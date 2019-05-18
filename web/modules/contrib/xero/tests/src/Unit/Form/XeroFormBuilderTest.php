<?php

namespace Drupal\Tests\xero\Unit\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Tests\UnitTestCase;
use Drupal\xero\Form\XeroFormBuilder;
use Drupal\xero\TypedData\Definition\AddressDefinition;
use Drupal\xero\TypedData\Definition\ContactDefinition;
use Drupal\xero\TypedData\Definition\PhoneDefinition;

/**
 * Class XeroFormBuilderTest
 *
 * @group Xero
 * @coversDefaultClass \Drupal\xero\Form\XeroFormBuilder
 */
class XeroFormBuilderTest extends UnitTestCase {

  /**
   * @property \Drupal\xero\Form\XeroFormBuilder $formBuilder
   */
  protected $formBuilder;

  /**
   * @property \Drupal\xero\TypedData\Definition\ContactDefinition $contactDefinition
   */
  protected $contactDefinition;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create some data definitions.
    $this->contactDefinition = ContactDefinition::create('xero_contact');
    $addressDefinition = AddressDefinition::create('xero_address');
    $phoneDefinition = PhoneDefinition::create('xero_phone');
    $addressListDefinition = new ListDataDefinition([], $addressDefinition);
    $phoneListDefinition = new ListDataDefinition([], $phoneDefinition);

    // Mock Typed Data Manager.
    $typedDataManager = $this->getMockBuilder('\Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();
    $typedDataManager->expects($this->any())
      ->method('getDefaultConstraints')
      ->willReturn([]);
    $typedDataManager->expects($this->any())
      ->method('createDataDefinition')
      ->will($this->returnValueMap([
        ['xero_contact', $this->contactDefinition],
        ['xero_address', $addressDefinition],
        ['xero_phone', $phoneDefinition],
      ]));

    // Mock Cache with null backend.
    $cache = new Cache('xero_query');

    $this->formBuilder = new XeroFormBuilder($typedDataManager, $cache);

    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $typedDataManager);
    \Drupal::setContainer($container);
  }

  /**
   *
   */
  public function testGetElementFor() {
    // Get the full element for a contact data definition.
    $element = $this->formBuilder->getElementFor('xero_contact');

    // Assert element types.
    $this->assertEquals('textfield', $element['Name']['#type']);
    $this->assertEquals('Name', $element['Name']['#title']);
    $this->assertEquals('checkbox', $element['IsSupplier']['#type']);
    $this->assertEquals('Is supplier?', $element['IsSupplier']['#title']);
    $this->assertEquals('container', $element['Addresses']['#type']);
    $this->assertEquals('select', $element['Addresses'][0]['AddressType']['#type']);
    $this->assertEquals(['POBOX' => 'POBOX', 'STREET' => 'STREET', 'DELIVERY' => 'DELIVERY'], $element['Addresses'][0]['AddressType']['#options']);

    // Assert that read-only property is not added to the element.
    $this->assertArrayNotHasKey('Website', $element);
  }

  public function testGetIDElement() {
    $element = $this->formBuilder->getElementForDefinition($this->contactDefinition, 'ContactID');

    $this->assertEquals('xero.autocomplete', $element['#autocomplete_route_name']);
    $this->assertEquals(['type' => 'xero_contact'], $element['#autocomplete_route_parameters']);
  }

}
