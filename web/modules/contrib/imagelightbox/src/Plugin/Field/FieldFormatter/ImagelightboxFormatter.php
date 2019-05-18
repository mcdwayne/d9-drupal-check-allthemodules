<?php

namespace Drupal\imagelightbox\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'imagelightbox' formatter.
 *
 * @FieldFormatter(
 *   id = "imagelightbox",
 *   module = "imagelightbox",
 *   label = @Translation("ImageLightBox"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageLightBoxFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => 'thumbnail',
      'imagelightbox_image_style' => 'large',
      'label' => 'hidden',
      'captions_source' => 'image_title',
      'buttons' => TRUE,
      'inline' => TRUE
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $settings = $this->getSettings();
    $image_styles = image_style_options(FALSE);

    $element['image_style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles
    ];

    $element['imagelightbox_image_style'] = $element['image_style'];
    $element['imagelightbox_image_style']['#title'] = t('ImageLightBox image style (default)');
    $element['imagelightbox_image_style']['#default_value'] = $this->getSetting('imagelightbox_image_style');

    $element['captions_source'] = [
      '#title' => t('Captions source'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('captions_source'),
      '#options' => $this->captionsSourceOptions()
    ];

    $element['inline'] = [
      '#title' => t('Display as inline elements'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('inline')
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $image_styles = image_style_options(FALSE);

    // Unset possible 'No defined styles' option.
    unset($image_styles['']);

    $image_style_setting = $this->getSetting('image_style');
    $style = isset($image_styles[$image_style_setting]) ?
        $image_styles[$image_style_setting] : t('Original image');
    $summary[] = t('Image style: @style', ['@style' => $style]);

    $image_style_setting = $this->getSetting('imagelightbox_image_style');
    $style = isset($image_styles[$image_style_setting]) ?
      $image_styles[$image_style_setting] : t('Original image');
    $summary[] = t('ImageLightBox image style (default): @style', ['@style' => $style]);


    $captions_source_options = $this->captionsSourceOptions();
    $summary[] = t('Captions source: @captions_source', ['@captions_source' => $captions_source_options[$this->getSetting('captions_source')]]);

    $summary[] = t('Inline: @inline', ['@inline' => $this->getBooleanSettingLabel('inline')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];

    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $settings = $this->getSettings();

    $cache_tags = [];
    if ($settings['image_style']) {
      $image_style = $this->imageStyleStorage->load($settings['image_style']);
      $cache_tags = $image_style->getCacheTags();
    }

    // Prepare image styles.
    if ($settings['imagelightbox_image_style']) {
      /** @var \Drupal\image\ImageStyleInterface $imagelightbox_image_style */
      $imagelightbox_image_style = $this->imageStyleStorage->load($settings['imagelightbox_image_style']);
    }

    $imagelightbox_image_style_responsive = [];
        if (isset($settings['imagelightbox_image_style_responsive'])){
          foreach ($settings['imagelightbox_image_style_responsive'] as $preset) {
            if ($preset['width']) {
              $imagelightbox_image_style_responsive[$preset['width']] = $this->imageStyleStorage->load($preset['image_style']);
            }
          }
        }

    /** @var \Drupal\file\FileInterface[] $files */
    foreach ($files as $delta => $file) {
      $image_uri = $file->getFileUri();
      $default_url = file_create_url($image_uri);
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      // Prepare caption
      if ($settings['captions_source'] == 'image_alt') {
        $caption = $item->get('alt')->getValue();
      } elseif ($settings['captions_source'] == 'image_title') {
        $caption = $item->get('title')->getValue();
      } else {
        $caption = '';
      }
      $link_attributes = [
        'class' => 'lightbox',
        'data-imagelightbox' => 'g',
        'data-ilb2-caption' => $caption,
      ];
      $item_attributes = [
        'class' => 'imagelightbox',
      ];

      $elements[$delta] = [
        '#theme' => 'imagelightbox_formatter',
        '#class' => 'imagelightbox',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#link_attributes' => $link_attributes,
        '#image_style' => $settings['image_style'],
        '#url' => empty($imagelightbox_image_style) ? $default_url : $imagelightbox_image_style->buildUrl($image_uri),
        '#cache' => [
          'tags' => $cache_tags,
          'contexts' => isset($cache_contexts) ? $cache_contexts : "",
        ],
      ];
    };
    $elements['#attached']['drupalSettings']['imagelightbox'] = $settings;
    $elements['#attached']['library'][] = 'imagelightbox/formatter';
    //$elements['#attributes']['class'][] = 'imagelightbox';
    if ($settings['inline']) {
        $elements['#attributes']['class'][] = 'container-inline';
    }

    return $elements;
  }

  /**
   * Returns animation options.
   */
  protected function animationOptions() {
    return [
      'none' => t('None'),
      'slideIn' => t('Slide'),
      'fadeIn' => t('Fade'),
    ];
  }

  /**
   * Returns captions source options.
   */
  protected function captionsSourceOptions() {
    return [
      'none' => t('None'),
      'image_title' => t('Image title'),
      'image_alt' => t('Image alt'),
    ];
  }

  /**
   * Returns labels for boolean settings.
   */
  protected function getBooleanSettingLabel($setting) {
    return $this->getSetting($setting) ? t('Yes') : t('No');
  }

}

