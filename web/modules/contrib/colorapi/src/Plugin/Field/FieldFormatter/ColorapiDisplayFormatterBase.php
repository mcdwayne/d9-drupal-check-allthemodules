<?php

namespace Drupal\colorapi\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\colorapi\Plugin\Field\FieldType\ColorItem;

/**
 * Base class for Color API Color Field Formatters.
 */
abstract class ColorapiDisplayFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_name' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['display_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display human-readable name'),
      '#description' => $this->t('When this box is checked, the human-readable name will be included in the output'),
      '#default_value' => $this->getSetting('display_name'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary['overview'] = $this->t('Displays the textual representation of the color');
    $summary['display_name'] = $this->t('Display the human-readable color name: @display_name', ['@display_name' => ($this->getSetting('display_name') ? $this->t('Yes') : $this->t('No'))]);

    return $summary;
  }

  /**
   * Adds the human-readable color name to the output.
   *
   * Note: Only added if the formatter settings are set to display the
   * human-readable name, and a value exists for the name.
   *
   * @param array $element
   *   The field formatter render array element the name is to be added to.
   * @param int $delta
   *   The index of the element within a multivalue field.
   * @param \Drupal\colorapi\Plugin\Field\FieldType\ColorItem $item
   *   The item being viewed.
   */
  protected function addHumanReadableNameToElement(array &$element, $delta, ColorItem $item) {
    if ($this->getSetting('display_name') && $color_name = $item->getColorName()) {
      if (strlen($color_name)) {
        $element[$delta]['name'] = [
          '#prefix' => '<p class="color_name">',
          '#suffix' => '</p>',
          '#markup' => $this->t('Color: @color', ['@color' => $item->getColorName()]),
        ];
      }
    }
  }

}
