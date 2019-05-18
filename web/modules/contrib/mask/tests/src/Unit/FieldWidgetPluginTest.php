<?php

namespace Drupal\Tests\mask\Unit;

use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormState;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\mask\Plugin\FieldWidgetPlugin;

/**
 * @coversDefaultClass Drupal\mask\Plugin\FieldWidgetPlugin
 * @group mask
 */
class FieldWidgetPluginTest extends MaskUnitTest {

  /**
   * The plugin being tested.
   *
   * @var \Drupal\mask\Plugin\FieldWidgetPlugin
   */
  private $plugin;

  /**
   * Tests if mask settings are added to the field widget form.
   */
  public function testFieldWidgetThirdPartySettingsForm() {
    $widget = $this->createWidget();
    $field_definition = new BaseFieldDefinition([]);

    $element = $this->plugin->fieldWidgetThirdPartySettingsForm($widget, $field_definition, 'default', [], new FormState());

    $this->assertArrayHasKey('value', $element);
    $this->assertArrayHasKey('reverse', $element);
    $this->assertArrayHasKey('clearifnotmatch', $element);
    $this->assertArrayHasKey('selectonfocus', $element);
  }

  /**
   * Tests if the #mask property is added to the proper element.
   */
  public function testFieldWidgetFormAlter() {
    $element = [
      '#type' => 'container',
      'value' => [
        '#type' => 'textfield',
      ],
    ];
    $widget = $this->createWidget([
      'mask' => [
        'value' => '00/00/0000',
        'clearifnotmatch' => TRUE,
      ],
    ]);
    $context['widget'] = $widget;

    $this->plugin->fieldWidgetFormAlter($element, new FormState(), $context);

    $this->assertArraySubset([
      '#mask' => [
        'value' => '00/00/0000',
        'clearifnotmatch' => TRUE,
      ],
    ], $element['value']);
  }

  /**
   * Creates a field widget with the provided third-party settings.
   */
  protected function createWidget($third_party_settings = []) {
    $field_definition = new BaseFieldDefinition([]);
    $widget = new StringTextfieldWidget('string_textfield', [], $field_definition, [], $third_party_settings);
    return $widget;
  }

  /**
   * Instantiates a plugin for the tests.
   */
  protected function setUp() {
    parent::setUp();

    // Mocks a URL generator.
    $url_generator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $url_generator->expects($this->any())
                   ->method('generateFromRoute')
                   ->with($this->equalTo('mask.settings'))
                   ->will($this->returnValue('http://example.com'));

    // Sets Drupal's service container.
    $container = new ContainerBuilder();
    $container->set('url_generator', $url_generator);
    \Drupal::setContainer($container);

    // Instantiates a FieldWidgetPlugin.
    $definition = [
      'defaults' => [
        'value' => '',
        'reverse' => FALSE,
        'clearifnotmatch' => FALSE,
        'selectonfocus' => FALSE,
      ],
      'element_parents' => ['value'],
    ];
    $this->plugin = new FieldWidgetPlugin([], 'string_textfield', $definition, $this->configFactory);
    $string_translation = $this->getStringTranslationStub();
    $this->plugin->setStringTranslation($string_translation);
  }

}
