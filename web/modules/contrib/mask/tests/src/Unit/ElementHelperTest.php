<?php

namespace Drupal\Tests\mask\Unit;

use Drupal\Core\Form\FormState;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\mask\Helper\ElementHelper;

/**
 * @coversDefaultClass \Drupal\mask\Helper\ElementHelper
 * @group mask
 */
class ElementHelperTest extends MaskUnitTest {

  /**
   * Tests if proper default values are added to element info.
   */
  public function testInfoAlter() {
    $info = [];
    $element = [
      '#type' => 'textfield',
    ];

    $helper = new ElementHelper();
    $helper->elementInfoAlter($info);

    $this->assertArraySubset([
      '#mask' => [
        'value' => '',
        'reverse' => FALSE,
        'clearifnotmatch' => FALSE,
        'selectonfocus' => FALSE,
      ],
    ], $info);
    $this->assertContains([get_class($helper), 'processElement'], $info['#process']);
  }

  /**
   * Tests if attributes are added to element that has "#mask" set.
   */
  public function testProcessElement() {
    $element = [
      '#type' => 'textfield',
      '#mask' => [
        'value' => '099.099.099.099',
        'reverse' => TRUE,
      ],
    ];

    $form = [];
    ElementHelper::processElement($element, new FormState(), $form);

    $this->assertEquals($this->translation, $element['#attached']['drupalSettings']['mask']['translation']);
    $this->assertContains('mask/mask', $element['#attached']['library']);
  }

  /**
   * Tests if libraries are not attached to an element with empty mask.
   */
  public function testProcessElementNoValue() {
    $element = [
      '#type' => 'textfield',
      '#mask' => [
        'reverse' => TRUE,
      ],
      '#attributes' => [],
    ];

    $form = [];
    ElementHelper::processElement($element, new FormState(), $form);

    $this->assertTrue(empty($element['#attached']));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Sets Drupal's service container.
    $container = new ContainerBuilder();
    $container->set('config.factory', $this->configFactory);
    \Drupal::setContainer($container);
  }

}
