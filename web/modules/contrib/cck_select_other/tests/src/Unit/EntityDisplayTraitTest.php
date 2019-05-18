<?php

namespace Drupal\Tests\cck_select_other\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the entity display trait.
 *
 * Note that prophecy cannot be used to mock because there is a conflict between
 * the base mockForTrait method and prophecy. PHPUnitWTF.
 *
 * @group cck_select_other
 */
class EntityDisplayTraitTest extends UnitTestCase {

  protected $mock;

  /**
   * The entity manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->mock = $this->getMockForTrait('Drupal\cck_select_other\EntityDisplayTrait');

    // Mock the entity manager.
    $this->entityManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityManager);
    \Drupal::setContainer($container);
  }

  /**
   * Assert that select other widget is detected on form displays.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param bool $expected
   *   The expected result.
   *
   * @dataProvider hasSelectOtherWidgetProvider
   */
  public function testHasSelectOtherWidget($plugin_id, $expected) {
    // Mock Field plugin settings.
    $fieldSettings = $this->getMockBuilder('\Drupal\Core\Field\PluginSettingsInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $fieldSettings
      ->expects($this->any())
      ->method('getPluginId')
      ->willReturn($plugin_id);

    // Mock Entity Form Display.
    $entityDisplay = $this->getMockBuilder('\Drupal\Core\Entity\Display\EntityDisplayInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityDisplay
      ->expects($this->any())
      ->method('getRenderer')
      ->with('field_list')
      ->willReturn($fieldSettings);

    // Mock the entity storage interface.
    $entityStorage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityStorage
      ->expects($this->any())
      ->method('loadByProperties')
      ->with(['targetEntityType' => 'entity_test'])
      ->willReturn(['entity_test.display.mock' => $entityDisplay]);

    // Mock entity manager methods.
    $this->entityManager
      ->expects($this->any())
      ->method('getStorage')
      ->with('entity_form_display')
      ->willReturn($entityStorage);

    // Mock the definition.
    $definition = $this->getMockBuilder('\Drupal\Core\Field\FieldDefinitionInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $definition
      ->expects($this->any())
      ->method('getTargetEntityTypeId')
      ->willReturn('entity_test');
    $definition
      ->expects($this->any())
      ->method('getName')
      ->willReturn('field_list');

    $this->assertEquals($expected, $this->mock->hasSelectOtherWidget($definition));
  }

  /**
   * Provide parameters for testHasSelectOtherWidget().
   *
   * @return array
   *   An array of test parameters.
   */
  public function hasSelectOtherWidgetProvider() {
    return [
      ['cck_select_other', TRUE],
      ['textfield', FALSE],
    ];
  }

  /**
   * Asserts that select other widget settings are returned or not.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $expected
   *   The expected result.
   *
   * @dataProvider getWidgetSettingsProvider().
   */
  public function testGetWidgetSettings($plugin_id, array $expected) {
    // Mock Field plugin settings.
    $fieldSettings = $this->getMockBuilder('\Drupal\Core\Field\PluginSettingsInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $fieldSettings
      ->expects($this->any())
      ->method('getPluginId')
      ->willReturn($plugin_id);
    $fieldSettings
      ->expects($this->any())
      ->method('getSettings')
      ->willReturn($expected);

    // Mock Entity Form Display.
    $entityDisplay = $this->getMockBuilder('\Drupal\Core\Entity\Display\EntityDisplayInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityDisplay
      ->expects($this->any())
      ->method('getRenderer')
      ->with('field_list')
      ->willReturn($fieldSettings);

    // Mock the entity storage interface.
    $entityStorage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityStorage
      ->expects($this->any())
      ->method('loadByProperties')
      ->with(['targetEntityType' => 'entity_test'])
      ->willReturn(['entity_test.display.mock' => $entityDisplay]);

    // Mock entity manager methods.
    $this->entityManager
      ->expects($this->any())
      ->method('getStorage')
      ->with('entity_form_display')
      ->willReturn($entityStorage);

    // Mock the definition.
    $definition = $this->getMockBuilder('\Drupal\Core\Field\FieldDefinitionInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $definition
      ->expects($this->any())
      ->method('getTargetEntityTypeId')
      ->willReturn('entity_test');
    $definition
      ->expects($this->any())
      ->method('getName')
      ->willReturn('field_list');

    $this->assertEquals($expected, $this->mock->getWidgetSettings($definition));
  }

  /**
   * Get test parameters for testGetWidgetSettings().
   *
   * @return array
   *   Test parameters.
   */
  public function getWidgetSettingsProvider() {
    return [
      ['cck_select_other', ['other_label' => 'Other']],
      ['textfield', []],
    ];
  }

}
