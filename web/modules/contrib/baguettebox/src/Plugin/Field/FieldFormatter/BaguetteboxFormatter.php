<?php

namespace Drupal\baguettebox\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'baguettebox' formatter.
 *
 * @FieldFormatter(
 *   id = "baguettebox",
 *   module = "baguettebox",
 *   label = @Translation("BaguetteBox"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class BaguetteboxFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'baguette_image_style' => '',
      'baguette_image_style_responsive' => [
        ['width' => NULL, 'image_style' => NULL],
        ['width' => NULL, 'image_style' => NULL],
        ['width' => NULL, 'image_style' => NULL],
        ['width' => NULL, 'image_style' => NULL],
        ['width' => NULL, 'image_style' => NULL],
      ],
      'animation' => 'slideIn',
      'captions_source' => 'image_alt',
      'buttons' => TRUE,
      'fullscreen' => FALSE,
      'hide_scrollbars' => FALSE,
      'inline' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $settings = $this->getSettings();
    $image_styles = image_style_options(FALSE);

    $element['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
    ];

    $element['baguette_image_style'] = $element['image_style'];
    $element['baguette_image_style']['#title'] = $this->t('Baguettebox image style (default)');
    $element['baguette_image_style']['#default_value'] = $this->getSetting('baguette_image_style');

    $element['baguette_image_style_responsive'] = [
      '#type' => 'item',
      '#title' => $this->t('Baguettebox image style (responsive)'),
      '#tree' => TRUE,
    ];

    for ($i = 0; $i <= 4; $i++) {
      $element['baguette_image_style_responsive'][$i] = [
        '#type' => 'container',
        '#attributes' => ['class' => 'container-inline'],
      ];
      $element['baguette_image_style_responsive'][$i]['width'] = [
        '#type' => 'number',
        '#title' => $this->t('Width'),
        '#title_display' => 'none',
        '#placeholder' => $this->t('Width'),
        '#min' => 1,
        '#max' => 99000,
        '#default_value' => $settings['baguette_image_style_responsive'][$i]['width'],
      ];
      $element['baguette_image_style_responsive'][$i]['image_style'] = [
        '#title' => $this->t('Image style'),
        '#title_display' => 'none',
        '#type' => 'select',
        '#default_value' => $settings['baguette_image_style_responsive'][$i]['image_style'],
        '#empty_option' => $this->t('None (original image)'),
        '#options' => $image_styles,
      ];
    }

    $element['animation'] = [
      '#title' => $this->t('Animation'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('animation'),
      '#options' => $this->animationOptions(),
    ];

    $element['captions_source'] = [
      '#title' => $this->t('Captions source'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('captions_source'),
      '#options' => $this->captionsSourceOptions(),
    ];

    $element['buttons'] = [
      '#title' => $this->t('Display buttons'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('buttons'),
    ];

    $element['fullscreen'] = [
      '#title' => $this->t('Enable full screen mode'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('fullscreen'),
    ];

    $element['hide_scrollbars'] = [
      '#title' => $this->t('Hide scrollbars when gallery is displayed'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('hide_scrollbars'),
    ];

    $element['inline'] = [
      '#title' => $this->t('Display as inline elements'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('inline'),
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
      $image_styles[$image_style_setting] : $this->t('Original image');
    $summary[] = $this->t('Image style: @style', ['@style' => $style]);

    $image_style_setting = $this->getSetting('baguette_image_style');
    $style = isset($image_styles[$image_style_setting]) ?
      $image_styles[$image_style_setting] : $this->t('Original image');
    $summary[] = $this->t('Baguette image style (default): @style', ['@style' => $style]);

    foreach ($this->getSetting('baguette_image_style_responsive') as $preset) {
      if ($preset['width']) {
        $style = isset($image_styles[$image_style_setting]) ?
          $image_styles[$image_style_setting] : $this->t('Original image');
        $summary[] = $this->t('Baguette image style (@width): @style', ['@width' => $preset['width'], '@style' => $style]);
      }
    }

    $animation_options = $this->animationOptions();
    $summary[] = $this->t('Animation: @animation', ['@animation' => $animation_options[$this->getSetting('animation')]]);

    $captions_source_options = $this->captionsSourceOptions();
    $summary[] = $this->t('Captions source: @captions_source', ['@captions_source' => $captions_source_options[$this->getSetting('captions_source')]]);

    $summary[] = $this->t('Display buttons: @display_buttons', ['@display_buttons' => $this->getBooleanSettingLabel('buttons')]);
    $summary[] = $this->t('Enable full screen mode: @fullscreen', ['@fullscreen' => $this->getBooleanSettingLabel('fullscreen')]);
    $summary[] = $this->t('Hide scrollbars: @hide_scrollbars', ['@hide_scrollbars' => $this->getBooleanSettingLabel('hide_scrollbars')]);
    $summary[] = $this->t('Inline: @inline', ['@inline' => $this->getBooleanSettingLabel('inline')]);

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

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if ($settings['image_style']) {
      $image_style = $this->imageStyleStorage->load($settings['image_style']);
      $cache_tags = $image_style->getCacheTags();
    }

    // Prepare image styles.
    if ($settings['baguette_image_style']) {
      /** @var \Drupal\image\ImageStyleInterface $baguette_image_style */
      $baguette_image_style = $this->imageStyleStorage->load($settings['baguette_image_style']);
    }

    $baguette_image_style_responsive = [];
    foreach ($settings['baguette_image_style_responsive'] as $preset) {
      if ($preset['width']) {
        $baguette_image_style_responsive[$preset['width']] = $this->imageStyleStorage->load($preset['image_style']);
      }
    }

    /** @var \Drupal\file\FileInterface[] $files */
    foreach ($files as $delta => $file) {
      $cache_contexts = [];
      $cache_contexts[] = 'url.site';
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $image_uri = $file->getFileUri();
      $default_url = file_create_url($image_uri);
      $link_attributes = [];
      foreach ($baguette_image_style_responsive as $width => $image_style) {
        $link_attributes['data-at-' . $width] = $image_style ? $image_style->buildUrl($image_uri) : $default_url;
      }

      $elements[$delta] = [
        '#theme' => 'baguettebox_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#link_attributes' => $link_attributes,
        '#image_style' => $settings['image_style'],
        '#url' => empty($baguette_image_style) ? $default_url : $baguette_image_style->buildUrl($image_uri),
        '#cache' => [
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ],
      ];
    }

    $elements['#attached']['drupalSettings']['baguettebox'] = $settings;
    $elements['#attached']['library'][] = 'baguettebox/formatter';
    $elements['#attributes']['class'][] = 'baguettebox';
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
      'none' => $this->t('None'),
      'slideIn' => $this->t('Slide'),
      'fadeIn' => $this->t('Fade'),
    ];
  }

  /**
   * Returns captions source options.
   */
  protected function captionsSourceOptions() {
    return [
      'none' => $this->t('None'),
      'image_title' => $this->t('Image title attribute'),
      'image_alt' => $this->t('Image alt attribute'),
    ];
  }

  /**
   * Returns labels for boolean settings.
   */
  protected function getBooleanSettingLabel($setting) {
    return $this->getSetting($setting) ? $this->t('Yes') : $this->t('No');
  }

}
