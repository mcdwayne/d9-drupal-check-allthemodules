<?php

namespace Drupal\Tests\pdb_vue\Unit\Form;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pdb_vue\Form\VueForm;

/**
 * @coversDefaultClass \Drupal\pdb_vue\Form\VueForm
 * @group pdb_vue
 */
class VueFormTest extends UnitTestCase {

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * Form State stub.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Form instance.
   *
   * @var \Drupal\pdb_vue\Form\VueForm
   */
  protected $form;

  /**
   * Create the setup for constants and configFactory stub.
   */
  protected function setUp() {
    parent::setUp();

    // Stub the Config Factory.
    $this->configFactory = $this->getConfigFactoryStub([
      'pdb_vue.settings' => [
        'development_mode' => 0,
        'use_spa' => 0,
        'spa_element' => '#element',
      ],
    ]);

    // Mock the formState.
    $this->formState = $this->getMock(FormStateInterface::CLASS);

    $this->form = new VueForm(
      $this->configFactory
    );

    // Create a translation stub for the t() method.
    $translator = $this->getStringTranslationStub();
    $this->form->setStringTranslation($translator);
  }

  /**
   * Tests the getFormId() method.
   */
  public function testGetFormId() {
    $expected = 'pdb_vue_form';
    $return = $this->form->getFormId();
    $this->assertEquals($expected, $return);
  }

  /**
   * Tests the buildForm() method.
   */
  public function testBuildForm() {
    $form = [];
    $result = $this->form->buildForm($form, $this->formState);

    $this->assertEquals('system_config_form', $result['#theme']);
    $this->assertEquals('0', $result['development_mode']['#default_value']);
    $this->assertEquals('0', $result['use_spa']['#default_value']);
    $this->assertEquals('#element', $result['spa_element']['#default_value']);
  }

  // submitForm() is not tested due to the parent having procedural functions.
}
