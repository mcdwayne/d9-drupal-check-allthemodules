<?php

namespace Drupal\text2image\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Plugin implementation of the 'text2image' formatter.
 *
 * @FieldFormatter(
 *   id = "text2image",
 *   label = @Translation("Text 2 Image"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class Text2ImageFormatter extends FormatterBase {

  protected $generator;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'width' => 220,
      'height' => 220,
      'bg_color' => '',
      'fg_color' => '',
      'font_file' => '',
      'font_size' => 20,
      'image_style' => '',
      'image_link' => '',
    ];
    return array_merge($settings, \Drupal::config('text2image.settings')->getRawData());
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings_link = Link::fromTextAndUrl(
      $this->t('Configure Text2Image settings'), Url::fromRoute('text2image.config_settings')
    );
    $element['settings_link'] = [
      '#type' => 'container',
      'link' => $settings_link->toRenderable(),
    ];
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
        $this->t('Configure Image Styles'), Url::fromRoute('entity.image_style.collection')
    );
    $element['image_style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable(),
      '#element_validate' => [[$this, 'changedImageStyle']],
    ];
    $element['image_link'] = [
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => ['content' => t('Content')],
    ];
    return $element;
  }

  /**
   * Invalidate image style cache tags when image_style selected.
   *
   * @param array $element
   *   Element array.
   * @param \Drupal\Core\Form\FormStateInterface\FormStateInterface $form_state
   *   Form state object.
   * @param array $form
   *   Form array.
   */
  public function changedImageStyle(array $element, FormStateInterface $form_state, array $form) {
    Cache::invalidateTags(['text2image:' . $element['#value']]);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t(
      'Generate: @width * @height, @fg on @bg, font size @size, style: @style',
      [
        '@width' => $this->getSetting('width'),
        '@height' => $this->getSetting('height'),
        '@size' => $this->getSetting('font_size'),
        '@style' => (empty($this->getSetting('image_style')) ? 'none' : $this->getSetting('image_style')),
        '@fg' => (empty($this->getSetting('fg_color')) ? '#random' : $this->getSetting('fg_color')),
        '@bg' => (empty($this->getSetting('bg_color')) ? '#random' : $this->getSetting('bg_color')),
      ]
    );
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $this->generator = \Drupal::service('text2image.generator')->init($this->getSettings());
    $image_style_setting = $this->getSetting('image_style');
    $cache_tags = $items->getEntity()->getCacheTags();
    $url = $items->getEntity()->toUrl();
    if (!empty($image_style_setting)) {
      $cache_tags = Cache::mergeTags($cache_tags, ImageStyle::load($image_style_setting)->getCacheTags());
      $cache_tags = Cache::mergeTags($cache_tags, ['text2image:' . $image_style_setting]);
    }
    $elements = [];
    foreach ($items as $delta => $item) {
      $image = $this->generator->getImage($item->value);
      $elements[$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $image,
        '#image_style' => $image_style_setting,
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];
      if ($this->getSetting('image_link') == 'content') {
        $elements[$delta]['#url'] = $item->getParent()->getEntity()->toUrl();
      }
    }
    return $elements;
  }

}
