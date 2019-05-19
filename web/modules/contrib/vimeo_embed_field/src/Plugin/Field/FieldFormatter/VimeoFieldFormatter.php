<?php

namespace Drupal\vimeo_embed_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'vimeo' formatter.
 *
 * @FieldFormatter(
 *   id = "vimeo",
 *   label = @Translation("Vimeo Embed Video"),
 *   field_types = {
 *     "vimeo"
 *   }
 * )
 */
class VimeoFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'vimeo_size' => '640*480',
      'vimeo_width' => '640',
      'vimeo_height' => '480',
      'vimeo_autoplay' => 0,
      'vimeo_loop' => 0,
      'vimeo_title' => 0,
      'vimeo_byline' => 0,
      'vimeo_portrait' => 0,
      'vimeo_color' => 0,
      'vimeo_color_value' => '0093cb',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $options = [
      '640*480' => '640px * 480px (4:3 aspect ratio)',
      '640*360' => '640px * 360px (16:9 aspect ratio)',
      '1280*720' => '1280px * 720px (16:9 aspect ratio)',
      'custom' => 'custom',
      'responsive' => 'Responsive',
    ];
    $elements['vimeo_size'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Vimeo video size'),
      '#default_value' => $this->getSetting('vimeo_size') ? $this->getSetting('vimeo_size') : 0,
    ];
    $elements['vimeo_autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay video'),
      '#default_value' => $this->getSetting('vimeo_autoplay'),
      '#description' => $this->t('Automatically play the video on load'),
    ];
    $elements['vimeo_loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop video.'),
      '#default_value' => $this->getSetting('vimeo_loop'),
      '#description' => $this->t('Play the video repeatedly.'),
    ];
    $elements['vimeo_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display title on video'),
      '#default_value' => $this->getSetting('vimeo_title'),
      '#description' => $this->t('Display the name of the video.'),
    ];
    $elements['vimeo_byline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display byline on video'),
      '#default_value' => $this->getSetting('vimeo_byline'),
      '#description' => $this->t('Display who the video is by.'),
    ];
    $elements['vimeo_portrait'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display portrait on video'),
      '#default_value' => $this->getSetting('vimeo_portrait'),
      '#description' => $this->t("Display the video submitter's picture or image."),
    ];
    $elements['vimeo_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#size' => 10,
      '#default_value' => $this->getSetting('vimeo_width') ? $this->getSetting('vimeo_width') : "",
      '#states' => [
        'visible' => [
          ':input[name*="vimeo_size"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $elements['vimeo_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#size' => 10,
      '#default_value' => $this->getSetting('vimeo_height') ? $this->getSetting('vimeo_height') : "",
      '#states' => [
        'visible' => [
          ':input[name*="vimeo_size"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $elements['vimeo_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set colors on video'),
      '#default_value' => $this->getSetting('vimeo_color'),
    ];
    $elements['vimeo_color_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color'),
      '#size' => 6,
      '#maxlength' => 6,
      '#default_value' => $this->getSetting('vimeo_color_value') ? $this->getSetting('vimeo_color_value') : '',
      '#states' => [
        'visible' => [
          ':input[name*="vimeo_color"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $videosize = $this->getSetting('vimeo_size');
    $summary[] = $this->t('Vimeo Embed Video (@videosize@autoplay@loop@title@byline@portrait).', [
      '@videosize' => $videosize,
      '@autoplay' => $this->getSetting('vimeo_autoplay') ? $this->t(', Auto play enabled') : '',
      '@loop' => $this->getSetting('vimeo_loop') ? $this->t(', Video loop enabled') : '',
      '@title' => $this->getSetting('vimeo_title') ? $this->t(', Video title enabled') : '',
      '@byline' => $this->getSetting('vimeo_byline') ? $this->t(', Video byline enabled') : '',
      '@portrait' => $this->getSetting('vimeo_portrait') ? $this->t(', Video portrait enabled') : '',
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $defaultSettings = $this->getSettings();
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'vimeo',
        '#url' => $this->getVimeoVideoIdFromUrl($item->vimeo_url, $defaultSettings),
        '#size' => $this->getSize($defaultSettings),
      ];
      if ($defaultSettings['vimeo_size'] == 'responsive') {
        $elements[$delta]['#attached']['library'][] = 'vimeo_embed_field/vimeo_embed_field.responsive';
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getVimeoVideoIdFromUrl($url = '', $settings = '') {
    $id = '';
    $color = $settings['vimeo_color'];
    if (($color != 0) && !empty($color)) {
      $color_value = $settings['vimeo_color_value'];
    }
    else {
      $color_value = '0093cb';
    }
    $id = vimeo_embed_field_get_vimeo_id_from_vimeo_url($url);
    if (($id['status'] == 1) && (!empty($id))) {
      $url = "https://player.vimeo.com/video/" . $id['video_id'] . "?autoplay=" . $settings['vimeo_autoplay'] . "&loop=" . $settings['vimeo_loop'] . "&title=" . $settings['vimeo_title'] . "&byline=" . $settings['vimeo_byline'] . "&color=" . $color_value . "&portrait=" . $settings['vimeo_portrait'];
      $id = $url;
    }
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSize($settings = '') {
    $size = [];
    $vimeo_size = $settings['vimeo_size'];
    if ($vimeo_size == 'responsive') {
      $size['width'] = '100%';
      $size['height'] = '100%';
    }
    elseif ($vimeo_size == 'custom') {
      $size['width'] = (int) $settings['vimeo_width'];
      $size['height'] = (int) $settings['vimeo_height'];
    }
    else {
      $width_height = explode('*', $vimeo_size);
      $size['width'] = (int) $width_height[0];
      $size['height'] = (int) $width_height[1];
    }
    return $size;
  }

}
