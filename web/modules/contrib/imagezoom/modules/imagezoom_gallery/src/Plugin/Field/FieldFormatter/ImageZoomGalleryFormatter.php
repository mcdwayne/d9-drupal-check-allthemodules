<?php

namespace Drupal\imagezoom_gallery\Plugin\Field\FieldFormatter;

use Drupal\imagezoom\Plugin\Field\FieldFormatter\ImageZoomFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Image Zoom gallery field formatter for Image fields.
 *
 * @FieldFormatter(
 *  id = "imagezoom_gallery",
 *  label = @Translation("Image Zoom Gallery"),
 *  field_types = {
 *     "image"
 *   }
 * )
 */
class ImageZoomGalleryFormatter extends ImageZoomFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'imagezoom_thumb_style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $image_styles = image_style_options(FALSE);

    $element['imagezoom_thumb_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Thumbnail image style'),
      '#options' => $image_styles,
      '#empty_option' => $this->t('None (original image)'),
      '#default_value' => $this->getSetting('imagezoom_thumb_style'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $image_styles = image_style_options(FALSE);
    unset($image_styles['']);

    $summary[] = t('Thumbnail image style: @style', [
      '@style' => isset($image_styles[$this->getSetting('imagezoom_thumb_style')]) ?
      $image_styles[$this->getSetting('imagezoom_thumb_style')] : 'original',
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = [
      'zoomType' => $this->getSetting('imagezoom_zoom_type'),
      'gallery' => 'imagezoom-thumb-wrapper',
    ];

    if ($this->getSetting('imagezoom_disable')) {
      $settings['responsive'] = TRUE;
      $settings['respond'] = [
        [
          'range' => '0 - ' . $this->getSetting('imagezoom_disable_width'),
          'enabled' => FALSE,
        ],
      ];
    }

    $additonal_settings = $this->settingsToArray($this->getSetting('imagezoom_additional'));
    $settings += $additonal_settings;

    $this->moduleHandler->alter('imagezoom_settings', $settings);

    $element[] = [
      '#theme' => 'imagezoom_gallery',
      '#items' => $items,
      '#display_style' => $this->getSetting('imagezoom_display_style'),
      '#zoom_style' => $this->getSetting('imagezoom_zoom_style'),
      '#thumb_style' => $this->getSetting('imagezoom_thumb_style'),
      '#settings' => $settings,
    ];

    $element['#attached'] = [
      'library' => [
        'imagezoom/elevatezoom',
      ],
      'drupalSettings' => [
        'imagezoom' => $settings,
      ],
    ];

    return $element;
  }

}
