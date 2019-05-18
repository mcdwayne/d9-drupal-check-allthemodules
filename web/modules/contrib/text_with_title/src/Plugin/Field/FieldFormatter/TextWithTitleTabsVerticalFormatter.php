<?php

namespace Drupal\text_with_title\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Plugin implementation of the 'TextWithTitleTabsVerticalFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "text_with_title_tabs_vertical_formatter",
 *   label = @Translation("Vertical Tabs"),
 *   field_types = {
 *     "text_with_title_field"
 *   }
 * )
 */
class TextWithTitleTabsVerticalFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Declare a setting named 'text_length', with
      // a default value of 'short'.
      'tabs_width' => '3',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['tabs_width'] = [
      '#title' => t('Tabs Width'),
      '#type' => 'select',
      '#options' => [
        '1' => '1 Column',
        '2' => '2 Column',
        '3' => '3 Column',
        '4' => '4 Column',
        '5' => '5 Column',
        '6' => '6 Column',
        '7' => '7 Column',
        '8' => '8 Column',
        '9' => '9 Column',
        '10' => '10 Column',
        '11' => '11 Column',
        '12' => '12 Column',
      ],
      '#default_value' => $this->getSetting('tabs_width'),
    ];

    return $element;
  }

  /**
   * Overide the view method so we can wrap the result in the accordion markup.
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    // Default the language to the current content language.
    if (empty($langcode)) {
      $langcode = \Drupal::languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }
    $elements = $this->viewElements($items, $langcode);

    $build = [
      '#theme' => 'text_with_title_tabs_vertical',
      '#tabs' => $elements,
      '#tabs_width' => $this->getSetting('tabs_width'),
    ];
    return $build;
  }

  /**
   * Define how the field type is displayed.
   *
   * Inside this method we can customize how the field is displayed inside
   * pages.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $id = Html::getUniqueId('bootstrap_tabs');
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        'title' => [
          '#plain_text' => $item->title,
        ],
        'active' => $delta == 0 ? TRUE : FALSE,
        'id' => $id . '--' . $delta,
        'text' => [
          '#type' => 'processed_text',
          '#text' => $item->text['value'],
          '#format' => $item->text['format'],
          '#langcode' => $langcode,
        ],
      ];
    }
    return $elements;
  }

}
