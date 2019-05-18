<?php

/**
 * @file
 * Theme specific functions for Feature Toggle module.
 */

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for module uninstall templates.
 *
 * Default template: system-modules-uninstall.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form. Child elements of the form
 *     features are individual features. Each feature is an associative array
 *     containing the following elements:
 *     - #label: The name of the module as a string.
 *     - #attributes: A list of attributes for the feature wrapper.
 *
 * @ingroup themeable
 */
function template_preprocess_feature_toggle_form(array &$variables) {
  $form = $variables['form'];
  $variables['features'] = [];

  // Iterate through all the modules, which are children of this element.
  foreach (Element::children($form['features']) as $key) {
    $feature = $form['features'][$key];
    $feature['feature_name'] = $key;
    $feature['label'] = $feature['#label'];
    $feature['checkbox'] = $form['status'][$key];
    $feature['checkbox_id'] = $form['status'][$key]['#id'];
    $feature['delete'] = feature_toggle_form_delete_link($key);
    $feature['attributes'] = new Attribute($feature['#attributes']);
    $variables['features'][] = $feature;
  }
}

/**
 * Returns the Delete field value for a specific feature.
 *
 * @param string $key
 *   The feature to process.
 *
 * @return \Drupal\Core\Link|\Drupal\Core\StringTranslation\TranslatableMarkup
 *   The row Delete field value depending on the user and the feature type.
 */
function feature_toggle_form_delete_link($key) {
  $account = \Drupal::currentUser();
  if ($account->hasPermission('administer feature_toggle')) {
    global $config;
    $hardcoded = isset($config['feature_toggle.features']['features']) ? $config['feature_toggle.features']['features'] : [];
    return isset($hardcoded[$key]) ?
      t('Immutable') :
      new Link(t('Delete'), Url::fromRoute('feature_toggle.feature_delete_form', ['feature_name' => $key]));
  }
  else {
    return t('Not allowed');
  }
}
