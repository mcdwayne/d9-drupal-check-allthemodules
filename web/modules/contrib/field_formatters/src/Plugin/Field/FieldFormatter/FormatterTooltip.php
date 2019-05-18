<?php

/**
 * @file
 * Contains \Drupal\field_formatters\Plugin\Field\FieldFormatter.
 */

namespace Drupal\field_formatters\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'tooltip' formatter.
 *
 * @FieldFormatter(
 *   id = "tooltip",
 *   label = @Translation("Show tooltip on hover"),
 *   field_types = {
 *     "string", "list_string", "text_with_summary", "text_long",
       "string_long", "text"
 *   }
 * )
 */

class FormatterTooltip extends FormatterBase {

    /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'tooltip' => t('Hi, iam a tooltip!!!'),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['tooltip'] = [
      '#type' => 'textfield',
      '#title' => t('Specify the tooltip for the text on hover'),
      '#size' => 20,
      '#required' => TRUE,
      '#default_value' => $this->getSetting('tooltip'),
     ];

     return $elements;
  }

    /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = t('Tooltip defined');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $qtip2_library_exists = _qtip2_library_exists();

    $message = t('The qtip2 library needs to be <a target="_blank" href="@url">downloaded</a> and extracted into the /libraries/qtip2 folder in your Drupal installation directory, to show the tooltip on text hover.', ['@url' => 'https://github.com/qTip2/qTip2/archive/master.zip']);

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'text_tooltip',
        '#text' => $item->value,
        '#tooltip' => $this->getSetting('tooltip'),
      ];

      if ($qtip2_library_exists) {
         $element[$delta]['#attached']['library'][] = 'field_formatters/text.tooltip.hover';
      }
      else {
        drupal_set_message($message, 'warning');
      }

    }

    return $element;
  }

}


