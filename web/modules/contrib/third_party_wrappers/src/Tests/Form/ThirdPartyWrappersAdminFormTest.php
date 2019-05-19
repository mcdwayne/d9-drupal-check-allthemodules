<?php

namespace Drupal\third_party_wrappers\Tests\Form;

use Drupal\Core\Form\FormState;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the admin form.
 *
 * @group third_party_wrappers
 *
 * @see \Drupal\third_party_wrappers\Form\ThirdPartyWrappersAdminForm
 */
class ThirdPartyWrappersAdminFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['third_party_wrappers'];

  /**
   * Test that the admin form is built correctly.
   */
  public function testBuildForm() {
    $form = \Drupal::formBuilder()->getForm('Drupal\third_party_wrappers\Form\ThirdPartyWrappersAdminForm');
    $this->assertEqual(TRUE, array_key_exists('expire_age', $form));
    $this->assertEqual(TRUE, array_key_exists('split_on', $form));
    $this->assertEqual(TRUE, array_key_exists('css_js_dir', $form));
  }

  /**
   * Test that the form submits and stores values correctly.
   */
  public function testSubmitForm() {
    // Set up the original values.
    $expire_age = 45;
    $split_on = '<!-- html_comment -->';
    $css_js_dir = 'custom/directory/structure';
    // Build the form state.
    $form_state = new FormState();
    $form_state->setValues([
      'expire_age' => $expire_age,
      'split_on' => $split_on,
      'css_js_dir' => $css_js_dir,
    ]);
    // Submit the form.
    \Drupal::formBuilder()->submitForm('Drupal\third_party_wrappers\Form\ThirdPartyWrappersAdminForm', $form_state);
    // Test the original values against values from the config, which should
    // have been placed there by ThirdPartyWrappersAdminForm::submitForm().
    $expire_age_config = \Drupal::config('third_party_wrappers.settings')->get('expire_age');
    $this->assertEqual($expire_age, $expire_age_config);
    $split_on_config = \Drupal::config('third_party_wrappers.settings')->get('split_on');
    $this->assertEqual($split_on, $split_on_config);
    $css_js_dir_config = \Drupal::config('third_party_wrappers.settings')->get('css_js_dir');
    $this->assertEqual($css_js_dir, $css_js_dir_config);
  }

}
