<?php

namespace Drupal\applenews\Plugin\applenews\ComponentType;

use Drupal\applenews\Plugin\ApplenewsComponentTypeBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin class to generate all the default Component plugins.
 *
 * @ApplenewsComponentType(
 *  id = "default_text",
 *  label = @Translation("Default Text Component Type"),
 *  description = @Translation("Default component types based on AppleNewsAPI library."),
 *  component_type = "text",
 *  deriver = "Drupal\applenews\Derivative\ApplenewsDefaultComponentTextTypeDeriver"
 * )
 */
class ApplenewsDefaultTextComponentType extends ApplenewsComponentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['component_settings']['component_data']['text'] = $this->getFieldSelectionElement($form_state, 'text', 'Source field for main text');

    $element['component_settings']['component_data']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => [
        'none' => $this->t('None'),
        'html' => $this->t('HTML'),
        'markdown' => $this->t('Markdown'),
      ],
      '#description' => $this->t('The formatting or markup method applied to the main text.'),
    ];

    return $element;
  }

}
