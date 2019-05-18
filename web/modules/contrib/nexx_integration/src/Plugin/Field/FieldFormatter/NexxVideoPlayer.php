<?php

namespace Drupal\nexx_integration\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'nexx_video_player' formatter.
 *
 * @FieldFormatter(
 *   id = "nexx_video_player",
 *   module = "nexx_integration",
 *   label = @Translation("Javascript Video Player"),
 *   field_types = {
 *     "nexx_video_data"
 *   }
 * )
 */
class NexxVideoPlayer extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('autoPlay: @value',
      ['@value' => $this->getOptions('autoPlay', $this->getSetting('autoPlay'))]
    );
    $summary[] = $this->t('exitMode: @value',
      ['@value' => $this->getOptions('exitMode', $this->getSetting('exitMode'))]
    );
    $summary[] = $this->t('Ad settings: @value',
      ['@value' => $this->getOptions('disableAds', $this->getSetting('disableAds'))]
    );
    $summary[] = $this->t('Streamtype: @value',
      ['@value' => $this->getOptions('streamType', $this->getSetting('advancedSettings')['streamType'])]
    );

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'autoPlay' => '0',
      'exitMode' => '',
      'disableAds' => '0',
      'advancedSettings' => [
        'streamType' => 'video',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * Helper function for easier setting display in settingsSummary().
   *
   * @return array|mixed|null
   *   Returns the option array or the setting value/name (or null).
   */
  public function getOptions() {
    $options = [
      'autoPlay' => [
        // @todo: add option to consider default setting in Omnia
        '0' => $this->t('Off'),
        '1' => $this->t('On'),
      ],
      'exitMode' => [
        '' => $this->t('Omnia Default'),
        'replay' => $this->t('replay'),
        'loop' => $this->t('loop'),
        'load' => $this->t('load'),
        'navigate' => $this->t('navigate'),
      ],
      'disableAds' => [
        '0' => $this->t('enabled'),
        '1' => $this->t('disabled'),
      ],
      'streamType' => [
        'video' => $this->t('video'),
        'audio' => $this->t('audio'),
        'live' => $this->t('live'),
        'radio' => $this->t('radio'),
        'scene' => $this->t('scene'),
        'playlist' => $this->t('playlist'),
        'audioalbum' => $this->t('audioalbum'),
        'videolist' => $this->t('videolist'),
        'audiolist' => $this->t('audiolist'),
        'collection' => $this->t('collection'),
        'panorama' => $this->t('panorama'),
      ],
    ];

    if (func_num_args() === 2) {
      return $options[func_get_arg(0)][func_get_arg(1)] ?? NULL;
    }

    if (func_num_args() === 1) {
      return $options[func_get_arg(0)] ?? [];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['autoPlay'] = [
      '#title' => $this->t('autoPlay'),
      '#type' => 'select',
      '#options' => $this->getOptions('autoPlay'),
      '#default_value' => $this->getSetting('autoPlay'),
    ];

    $element['exitMode'] = [
      '#title' => $this->t('exitMode'),
      '#type' => 'select',
      '#options' => $this->getOptions('exitMode'),
      '#default_value' => $this->getSetting('exitMode'),
    ];

    $element['disableAds'] = [
      '#title' => $this->t('Ad settings'),
      '#type' => 'select',
      '#options' => $this->getOptions('disableAds'),
      '#default_value' => $this->getSetting('disableAds'),
    ];

    $element['advancedSettings'] = [
      '#title' => $this->t('Advanced settings'),
      '#type' => 'details',
      'streamType' => [
        '#title' => $this->t('Stream type'),
        '#type' => 'select',
        '#options' => $this->getOptions('streamType'),
        '#default_value' => $this->getSetting('advancedSettings')['streamType'],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'nexx_player',
        '#container_id' => 'player--' . Crypt::randomBytesBase64(8),
        '#video_id' => $item->item_id,
        '#autoplay' => $this->getSetting('autoPlay'),
        '#exitMode' => $this->getSetting('exitMode'),
        '#disableAds' => $this->getSetting('disableAds'),
        '#streamType' => $this->getSetting('advancedSettings')['streamType'],
        '#attached' => [
          'library' => [
            'nexx_integration/base',
          ],
        ],
        /*
        '#cache' => [
          'tags' => $user->getCacheTags(),
        ],
         */
      ];
    }

    return $elements;
  }

}
