<?php

/**
 * @file
 * Contains \Drupal\swiftype_integration\Form\SwiftypeIntegrationSearchForm.
 */

namespace Drupal\swiftype_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the search form for the search block.
 */
class SwiftypeIntegrationSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'swiftype_integration_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Add swiftype css class to the form field. Otherwise external js library
    // will not found this field to put search data in
    $attributes['class'][] = 'st-default-search-input';

    $form['keys'] = [
      '#type' => 'search',
      '#size' => 15,
      '#default_value' => '',
      '#attributes' => $attributes,
    ];
    // Add swiftype.js with service shortcode
    $form['#attached']['library'][] = 'swiftype_integration/swiftype_js_library';
    // Pass install key setting to swiftype js library
    $form['#attached']['drupalSettings']['swiftype_integration']['install_key'] =
      \Drupal::config('swiftype_integration.settings')->get('swiftype_integration_install_key');

    return $form;
  }

  /**
   * {@inheritdoc}
   * This method should be implemented, because FormBase is abstract class
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
