<?php

namespace Drupal\applenews\Plugin\applenews\ComponentType;

use Drupal\applenews\Plugin\ApplenewsComponentTypeBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin class to generate all the default Component plugins.
 *
 * @ApplenewsComponentType(
 *  id = "default_image",
 *  label = @Translation("Default Image Component Type"),
 *  description = @Translation("Default component types based on AppleNewsAPI library."),
 *  component_type = "image",
 *  deriver = "Drupal\applenews\Derivative\ApplenewsDefaultComponentImageTypeDeriver"
 * )
 */
class ApplenewsDefaultImageComponentType extends ApplenewsComponentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['component_settings']['component_layout'] += $this->getMaximumContentWidthElement();

    $element['component_settings']['component_data']['URL'] = $this->getFieldSelectionElement($form_state, 'URL', 'Source field for URL');

    $element['component_settings']['component_data']['caption'] = $this->getFieldSelectionElement($form_state, 'caption', 'Source field for caption');

    return $element;
  }

}
