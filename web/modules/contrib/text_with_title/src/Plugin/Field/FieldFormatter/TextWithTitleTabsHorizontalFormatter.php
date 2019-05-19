<?php

namespace Drupal\text_with_title\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Implementation of the 'TextWithTitleTabsHorizontalFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "text_with_title_tabs_horizontal_formatter",
 *   label = @Translation("Horizontal Tabs"),
 *   field_types = {
 *     "text_with_title_field"
 *   }
 * )
 */
class TextWithTitleTabsHorizontalFormatter extends FormatterBase {

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
      '#theme' => 'text_with_title_tabs_horizontal',
      '#tabs' => $elements,
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
