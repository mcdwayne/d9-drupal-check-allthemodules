<?php

namespace Drupal\applenews\Plugin\applenews\ComponentType;

use Drupal\applenews\Plugin\ApplenewsComponentTypeBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin class for the Divider component type.
 *
 * @ApplenewsComponentType(
 *  id = "default_divider",
 *  label = @Translation("Divider"),
 *  description = @Translation("Default divider component type based on AppleNewsAPI library."),
 *  component_type = "divider",
 * )
 */
class ApplenewsDefaultDividerComponentType extends ApplenewsComponentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['component_settings']['component_layout'] += $this->getMaximumContentWidthElement();

    $element['component_settings']['component_data']['stroke_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Stroke width'),
      '#min' => 1,
      '#description' => $this->t('The width of the stroke in pts.'),
    ];

    $element['component_settings']['component_data']['stroke_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stroke color'),
      '#description' => $this->t('The hexadecimal value for the color. ex: #000000'),
      '#default_value' => '#000000',
    ];

    $element['component_settings']['component_data']['stroke_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Stroke style'),
      '#options' => [
        'solid' => $this->t('Solid'),
        'dashed' => $this->t('Dashed'),
        'dotted' => $this->t('Dotted'),
      ],
      '#description' => $this->t('The style of the stroke.'),
    ];

    return $element;
  }

}
