<?php

namespace Drupal\applenews\Plugin\applenews\ComponentType;

use Drupal\applenews\Plugin\ApplenewsComponentTypeBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin class to generate all the default Component plugins.
 *
 * @ApplenewsComponentType(
 *  id = "default_nested",
 *  label = @Translation("Default Nested Component Type"),
 *  description = @Translation("Default component types based on AppleNewsAPI library."),
 *  component_type = "nested",
 *  deriver = "Drupal\applenews\Derivative\ApplenewsDefaultComponentNestedDeriver"
 * )
 */
class ApplenewsDefaultNestedComponentType extends ApplenewsComponentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['component_settings']['component_data']['components'] = [
      '#type' => 'hidden',
      '#value' => NULL,
    ];

    return $element;
  }

}
