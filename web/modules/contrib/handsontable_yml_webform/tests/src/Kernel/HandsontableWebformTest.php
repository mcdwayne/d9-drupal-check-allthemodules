<?php

namespace Drupal\Tests\handsontable_yml_webform\Kernel;

use Drupal\handsontable_yml_webform\Plugin\WebformElement\Handsontable;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Just instanciates various classes to check for API compatibility between
 * Webform and this module.
 *
 * @group handsontable_yml_webform
 */
class HandsontableWebformTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'handsontable_yml_webform',
    'webform',
  ];

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('webform');
  }

  public function testCreateInstances() {
    // Create a WebformElementManager instance:
    $oManager = \Drupal::service('plugin.manager.webform.element');
    $oWebformElement = $oManager->createInstance('handsontable');
    self::assertInstanceOf(Handsontable::class, $oWebformElement);

    $oManager = \Drupal::service('plugin.manager.element_info');
    $oRenderElement = $oManager->createInstance('handsontable');
    self::assertInstanceOf(\Drupal\handsontable_yml_webform\Element\Handsontable::class, $oRenderElement);
  }
}
