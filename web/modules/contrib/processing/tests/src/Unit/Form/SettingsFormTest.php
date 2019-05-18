<?php

namespace Drupal\Tests\processing\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\processing\Form\SettingsForm;
use Drupal\Tests\UnitTestCase;

/**
 * Test the settings form methods.
 *
 * @coversDefaultClass \Drupal\processing\Form\SettingsForm
 *
 * @group processing
 */
class SettingsFormTest extends UnitTestCase {

  /**
   * Settings form.
   *
   * @var \Drupal\processing\Form\SettingsForm
   */
  protected $formObject;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $immutableConfig = $this->prophesize('\Drupal\Core\Config\ImmutableConfig');
    $immutableConfig
      ->get('defaults.processing_js_path')
      ->willReturn('/libraries/processing.js/processing.min.js');

    // Prophecy is opinionated about object chaining.
    $mutableConfig = $this->getMockBuilder('\Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $mutableConfig->expects($this->any())
      ->method('get')
      ->with('defaults.processing_js_path')
      ->will($this->onConsecutiveCalls(
        '/libraries/processing.js/processing.min.js',
        '/sites/all/libraries/processing.js/processing.js'
      ));
    $mutableConfig->expects($this->any())
      ->method('set')
      ->with('defaults.processing_js_path', '/sites/all/libraries/processing.js/processing.js')
      ->willReturn($mutableConfig);

    $configFactory = $this->prophesize('\Drupal\Core\Config\ConfigFactoryInterface');
    $configFactory->get('processing.settings')->willReturn($immutableConfig->reveal());
    $configFactory->getEditable('processing.settings')->willReturn($mutableConfig);

    $fileSystem = $this->prophesize('\Drupal\Core\File\FileSystemInterface');
    $fileSystem
      ->realpath('/sites/all/libraries/processing.js/processing.js')
      ->willReturn('/path/to/drupal/sites/all/libraries/processing.js/processing.js');
    $fileSystem
      ->realpath('garbage')
      ->willReturn(FALSE);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('config.factory', $configFactory->reveal());
    $container->set('file_system', $fileSystem->reveal());
    $container->setParameter('app.root', '');
    \Drupal::setContainer($container);

    $this->formObject = new SettingsForm($container->get('config.factory'), $container->get('file_system'), $container->getParameter('app.root'));
  }

  /**
   * Assert that the form structure is built correctly.
   *
   * @covers ::buildForm
   */
  public function testBuildForm() {
    $form_state = new FormState();
    $form = $this->formObject->buildForm([], $form_state);

    $this->assertArrayHasKey('actions', $form);
    $this->assertArrayHasKey('defaults', $form);
    $this->assertEquals('/libraries/processing.js/processing.min.js', $form['defaults']['processing_js_path']['#default_value']);
  }

  /**
   * Assert that form validation processes correctly.
   *
   * @param string $path
   *   The path to set to test validation.
   * @param bool $willPass
   *   The expected result of validation.
   *
   * @dataProvider formStateValuesProvider
   *
   * @covers ::validateForm
   */
  public function testValidateForm($path, $willPass) {
    $formState = new FormState();
    $element = ['#parents' => ['defaults', 'processing_js_path']];
    $form = $this->formObject->buildForm([], $formState);

    $formState->setValueForElement($element, $path);
    $this->formObject->validateForm($form, $formState);

    $this->assertEquals($willPass, empty($formState->getErrors()));
  }

  /**
   * Assert that form submission processes correctly.
   *
   * @covers ::submitForm
   */

  /**
   * Provide form state values for tests.
   *
   * @return array
   *   An array of form state values.
   */
  public function formStateValuesProvider() {
    return [
      ['garbage', FALSE],
      ['/sites/all/libraries/processing.js/processing.js', TRUE],
    ];
  }

}
