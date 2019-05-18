<?php

namespace Drupal\Tests\gridstack\Kernel\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\gridstack_ui\Form\GridStackSettingsForm;

/**
 * Tests the GridStack UI settings form.
 *
 * @coversDefaultClass \Drupal\gridstack_ui\Form\GridStackSettingsForm
 *
 * @group gridstack
 */
class GridStackSettingsFormTest extends KernelTestBase {

  /**
   * The gridstack settings form object under test.
   *
   * @var \Drupal\gridstack_ui\Form\GridStackSettingsForm
   */
  protected $gridstackSettingsForm;

  /**
   * Drupal\Core\Asset\LibraryDiscoveryInterface definition.
   *
   * @var Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'file',
    'image',
    'media',
    'blazy',
    'gridstack',
    'gridstack_ui',
  ];

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(static::$modules);

    $this->blazyManager = $this->container->get('blazy.manager');
    $this->libraryDiscovery = $this->container->get('library.discovery');
    $this->messenger = $this->container->get('messenger');

    $this->gridstackSettingsForm = new GridStackSettingsForm(
      $this->blazyManager->getConfigFactory(),
      $this->libraryDiscovery,
      $this->messenger
    );

    // Enable Boostrap support.
    $this->blazyManager->getConfigFactory()->getEditable('gridstack.settings')->set('framework', 'bootstrap')->save();
  }

  /**
   * Tests for \Drupal\gridstack_ui\Form\GridStackSettingsForm.
   *
   * @covers ::getFormId
   * @covers ::getEditableConfigNames
   * @covers ::buildForm
   * @covers ::submitForm
   */
  public function testGridStackSettingsForm() {
    // Emulate a form state of a submitted form.
    $form_state = (new FormState())->setValues([
      'customized' => TRUE,
    ]);

    $this->assertInstanceOf(FormInterface::class, $this->gridstackSettingsForm);
    $this->assertEquals('bootstrap', $this->blazyManager->getConfigFactory()->get('gridstack.settings')->get('framework'));

    $id = $this->gridstackSettingsForm->getFormId();
    $this->assertEquals('gridstack_settings_form', $id);

    $method = new \ReflectionMethod(GridStackSettingsForm::class, 'getEditableConfigNames');
    $method->setAccessible(TRUE);

    $name = $method->invoke($this->gridstackSettingsForm);
    $this->assertEquals(['gridstack.settings'], $name);

    $form = $this->gridstackSettingsForm->buildForm([], $form_state);
    $this->gridstackSettingsForm->submitForm($form, $form_state);
  }

}
