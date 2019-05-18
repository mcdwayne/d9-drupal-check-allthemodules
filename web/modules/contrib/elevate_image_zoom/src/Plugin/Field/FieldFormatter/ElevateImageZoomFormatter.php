<?php

namespace Drupal\elevate_image_zoom\Plugin\Field\FieldFormatter;

use Drupal\core\field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'elevate_image_zoom_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "elevate_image_zoom_formatter",
 *   label = @Translation("Elevate Image Zoom"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */

class ElevateImageZoomFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'elevate_zoom_image_style' => '',
      'elevate_zoom_type' => 'basic_zoom',
      'elevate_shadow_color' => '#000000',
      'elevate_window_position' => 1,
      'elevate_window_width' => 500,
      'elevate_window_height' => 500,
      'elevate_lens_size' => 100,
      'elevate_thumbnail' => 'thumbnail',
    ];
  }

  /**
   * Setting form for field formatter.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    unset($element['image_link']);
    $element['elevate_zoom_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Elevate Image Zoom Style'),
      '#default_value' => $this->getSetting('elevate_zoom_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => image_style_options(FALSE),
    ];
    $element['elevate_thumbnail'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Thumbnail Style'),
      '#default_value' => $this->getSetting('elevate_thumbnail'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => image_style_options(FALSE),
    ];
    $element['elevate_zoom_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Zoom Type'),
      '#description' => $this->t('Select what type of zoom effect you would like.'),
      '#default_value' => $this->getSetting('elevate_zoom_type'),
      '#options' => [
        'basic_zoom' => $this->t('Basic Zoom'),
        'tint_zoom' => $this->t('Tint Zoom'),
        'inner_zoom' => $this->t('Inner Zoom'),
        'lens_zoom' => $this->t('Lens Zoom'),
        'mousewheel_zoom' => $this->t('Mousewheel Zoom'),
      ],
    ];
    $element['elevate_shadow_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Overlay color'),
      '#description' => $this->t('Shadow color. default value is #000000.'),
      '#default_value' => $this->getSetting('elevate_shadow_color'),
      '#size' => 10,
      '#maxlength' => 15,
      '#required' => TRUE,
    ];
    $element['elevate_window_position'] = [
      '#type' => 'number',
      '#title' => $this->t('Zoom position'),
      '#description' => $this->t('Zoomed image popup position clock wise, Range 1 to 16.'),
      '#default_value' => $this->getSetting('elevate_window_position'),
      '#size' => 10,
      '#min' => 1,
      '#max' => 16,
      '#required' => TRUE,
    ];
    $element['elevate_window_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Zoom window width'),
      '#description' => $this->t('Zoomed window width, Range 100 to 1000.'),
      '#default_value' => $this->getSetting('elevate_window_width'),
      '#size' => 10,
      '#min' => 100,
      '#max' => 1000,
      '#required' => TRUE,
    ];
    $element['elevate_window_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Zoom window height'),
      '#description' => $this->t('Zoomed window width, Range 100 to 1000.'),
      '#default_value' => $this->getSetting('elevate_window_height'),
      '#size' => 10,
      '#min' => 100,
      '#max' => 1000,
      '#required' => TRUE,
    ];
    $element['elevate_lens_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Zoom lens size'),
      '#description' => $this->t('Lens size, Range 100 to 300.'),
      '#default_value' => $this->getSetting('elevate_lens_size'),
      '#size' => 10,
      '#min' => 100,
      '#max' => 300,
      '#required' => TRUE,
    ];
    return $element;
  }

  /**
   * Summery for field formatter.
   */
  public function settingsSummary() {
    $summary = [];
    $image_styles = image_style_options(FALSE);
    $style = 'Original image';
    if (isset($image_styles[$this->getSetting('image_style')])) {
      $style = $image_styles[$this->getSetting('image_style')];
    }
    $zoom_image_style = 'Original image';
    if (isset($image_styles[$this->getSetting('elevate_zoom_image_style')])) {
      $zoom_image_style = $image_styles[$this->getSetting('elevate_zoom_image_style')];
    }
    $summary[] = $this->t('Image style: @style', [
      '@style' => $style,
    ]);
    $summary[] = $this->t('Zoom image style: @zoom_image_style', [
      '@zoom_image_style' => $zoom_image_style,
    ]);
    $summary[] = $this->t('Zoom type: @zoom_type', [
      '@zoom_type' => $this->getSetting('elevate_zoom_type'),
    ]);
    return $summary;
  }

  /**
   * View element hook.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
    if (empty($images = $this->getEntitiesToView($items, $langcode))) {
      return $elements;
    }
    else {
      $image_style_setting = $this->imageStyleStorage->load($this->getSetting('image_style'));
      $zoom_image_style = $this->imageStyleStorage->load($this->getSetting('elevate_zoom_image_style'));
      $image_style_thumbnail = $this->imageStyleStorage->load($this->getSetting('elevate_thumbnail'));
      foreach ($images as $delta => $image) {
        $image_uri = $image->getFileUri();
        $zoom_image_url = $zoom_image_style ? $zoom_image_style->buildUrl($image_uri) : file_create_url($image_uri);
        $zoom_image_url = file_url_transform_relative($zoom_image_url);
        $image_url = $image_style_setting ? $image_style_setting->buildUrl($image_uri) : file_create_url($image_uri);
        $image_url = file_url_transform_relative($image_url);
        $thumbnail_image_url = $image_style_thumbnail ? $image_style_thumbnail->buildUrl($image_uri) : file_create_url($image_uri);
        $thumbnail_image_url = file_url_transform_relative($thumbnail_image_url);
        $images_array[$delta]['images_url'] = $image_url;
        $images_array[$delta]['zoom_image_url'] = $zoom_image_url;
        $images_array[$delta]['thumbnail'] = $thumbnail_image_url;
        $classes = 'elevate_zoom--' . $this->getSetting('elevate_zoom_type');
      }
      if (count($images_array) > 1) {
        $classes .= '_gallery';
        $has_gallery = 'yes';
      }
      else {
        $has_gallery = 'no';
      }
      $elements = [
        '#theme' => 'elevate_image_zoom_template',
        '#elevate_images' => $images_array,
        '#elevate_class' => $classes,
        '#elevate_has_gallery' => $has_gallery,
      ];
      $elements['#attached']['library'][] = 'elevate_image_zoom/elevate_image_zoom_js';
      $elements['#attached']['drupalSettings']['elevate_tint_shadow_color'] = $this->getSetting('elevate_shadow_color');
      $elements['#attached']['drupalSettings']['elevate_window_position'] = $this->getSetting('elevate_window_position');
      $elements['#attached']['drupalSettings']['elevate_window_width'] = $this->getSetting('elevate_window_width');
      $elements['#attached']['drupalSettings']['elevate_window_height'] = $this->getSetting('elevate_window_height');
      $elements['#attached']['drupalSettings']['elevate_lens_size'] = $this->getSetting('elevate_lens_size');
      return $elements;
    }
  }

}
