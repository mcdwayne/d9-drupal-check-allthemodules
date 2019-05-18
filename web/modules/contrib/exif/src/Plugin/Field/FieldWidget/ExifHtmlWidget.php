<?php

namespace Drupal\exif\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'exif_html' widget.
 *
 * @FieldWidget(
 *   id = "exif_html",
 *   label = @Translation("metadata from image as html table"),
 *   description = @Translation("field content is calculated from image field
 *   in the same content type (field are hidden from forms)"), multiple_values
 *   = true, field_types = {
 *     "text",
 *     "text_long",
 *   }
 * )
 */
class ExifHtmlWidget extends ExifWidgetBase {

  const EXIF_HTML_DEFAULT_SETTINGS = [
    'exif_field_separator' => '',
    'exif_field' => 'all_all',
  ];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ExifHtmlWidget::EXIF_HTML_DEFAULT_SETTINGS + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => '',
      '#value' => '',
    ];
    return $element;
  }

}
