<?php

namespace Drupal\config_overridden\Tests;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormState;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests overrides for SiteInformationForm.
 *
 * @group config_overridden
 * @package Drupal\config_overridden\Tests
 */
class SiteInformationFormTest extends WebTestBase {
  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['config_overridden', 'node'];

  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test case for form.
   */
  public function testFormOverrides() {
    $config = \Drupal::configFactory()->getEditable('system.site');
    $form_state = new FormState();
    $form_state->disableCache();
    $form_original = \Drupal::formBuilder()->buildForm('Drupal\system\Form\SiteInformationForm', $form_state);

    $this->assertTrue(isset($form_original['#config_overridden_processed']), 'Form was processed by overrider');

    $map = [
      'name' => 'site_information.site_name',
      'slogan' => 'site_information.site_slogan',
      'mail' => 'site_information.site_mail',
      'page.front' => 'front_page.site_frontpage',
      'page.404' => 'error_page.site_404',
      'page.403' => 'error_page.site_403',
    ];

    $types = [
      'page.front' => 'uri',
      'page.404' => 'uri',
      'page.403' => 'uri',
    ];

    $values = [];
    foreach ($map as $property => $form_path) {
      $type = isset($types[$property]) ? $types[$property] : 'string';
      $random_value = crc32(uniqid($property . $form_path . time()));
      switch ($type) {
        case 'uri':
          $random_value = $this->createNodeGetPath();
          break;
        default:
          break;
      }

      $values[$property] = $random_value;
      $config->set($property, $random_value);
    }

    // Changing configs emulating that it is done in settings.php
    $GLOBALS['config']['system.site'] = $config->get();
    unset($config);

    // Get back original config.
    $config = \Drupal::configFactory()
      ->reset('system.site')
      ->get('system.site');

    $form_state = new FormState();
    $form_state->disableCache();
    $form_overridden = \Drupal::formBuilder()->buildForm('Drupal\system\Form\SiteInformationForm', $form_state);

    $this->assertTrue(in_array('config_overridden/config-highlight', $form_overridden['#attached']['library']), 'Library for highlighting is loaded');

    foreach ($map as $property => $form_path) {
      $value_to_match = $values[$property];
      $path = explode('.', $form_path);
      $old_element = NestedArray::getValue($form_original, $path);
      $element = NestedArray::getValue($form_overridden, $path);


      if (isset($element['#config_overridden_value'])) {
        $this->assertEqual($element['#config_overridden_value'], $config->getOriginal($property, FALSE), 'Stored value is set correctly');
      }
      else {
        $this->fail("Element {$form_path} doesn't have #config_overridden_value");
      }

      $this->assertEqual($element['#default_value'], $value_to_match, "Element {$property} old value {$old_element['#default_value']} and new value {$element['#default_value']}");
      $this->assertTrue(strpos($element['#title'], 'overrides') !== FALSE, 'Element ' . $form_path . ' title is changed?');

    }

  }

  /**
   * Creates new node and gets path to it.
   *
   * @return string
   *   Path to node.
   */
  protected function createNodeGetPath() {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node' . time(),
    ]);

    $node->save();
    return '/node/' . $node->id();
  }

}
