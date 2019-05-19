<?php

namespace Drupal\bxslider\Plugin\Field\FieldFormatter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Cache\Cache;

/**
 * BxSlider thumbnail pager.
 *
 * @FieldFormatter(
 *  id = "bxslider_ths",
 *  label = @Translation("BxSlider - Thumbnail slider"),
 *  field_types = {"image", "media"}
 * )
 */
class BxsliderThs extends Bxslider implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $bxslider_settings = parent::defaultSettings();
    $bxslider_settings['slider']['pager']['pager'] = FALSE;

    return [
      'thumbnail_slider' => [
        'thumbnail_style' => 'thumbnail',
        'general' => [
          'mode' => 'horizontal',
          'speed' => 500,
          'slideMargin' => 0,
          'startSlide' => 0,
          'randomStart' => FALSE,
          'infiniteLoop' => FALSE,
          'hideControlOnEnd' => TRUE,
          'easing' => '',
          'captions' => FALSE,
          'ticker' => FALSE,
          'tickerHover' => FALSE,
          'adaptiveHeight' => FALSE,
          'adaptiveHeightSpeed' => 500,
          'video' => FALSE,
          'responsive' => TRUE,
          'useCSS' => TRUE,
          'preloadImages' => 'visible',
          'touchEnabled' => TRUE,
          'swipeThreshold' => 50,
          'oneToOneTouch' => TRUE,
          'preventDefaultSwipeX' => TRUE,
          'preventDefaultSwipeY' => FALSE,
          'wrapperClass' => 'bx-wrapper',
        ],
        'pager' => [
          'pager' => FALSE,
          'pagerType' => 'full',
          'pagerShortSeparator' => ' / ',
          'pagerSelector' => '',
        // 'pagerCustom' => 'null',.
        ],
        'controls' => [
          'controls' => TRUE,
          'nextText' => 'Next',
          'prevText' => 'Prev',
          'nextSelector' => '',
          'prevSelector' => '',
          'autoControls' => FALSE,
          'startText' => 'Start',
          'stopText' => 'Stop',
          'autoControlsCombine' => FALSE,
          'autoControlsSelector' => '',
        ],
        'auto' => [
          'auto' => FALSE,
          'pause' => 4000,
          'autoStart' => TRUE,
          'autoDirection' => 'next',
          'autoHover' => FALSE,
          'autoDelay' => 0,
        ],
        'carousel' => [
          'minSlides' => 4,
          'maxSlides' => 4,
          'moveSlides' => 1,
          'slideWidth' => 0,
        ],
      ],
    ] + $bxslider_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    // Hide Pager options, because here is used Thumbnail image slider.
    $elements['slider']['pager']['#access'] = FALSE;

    $settings = $this->getSettings();

    $image_styles = image_style_options(FALSE);

    $elements['thumbnail_slider'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Thumbnail slider'),
      '#weight' => 10,
    ];

    $elements['thumbnail_slider']['thumbnail_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $settings['thumbnail_slider']['thumbnail_style'],
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
    ];

    $elements['thumbnail_slider']['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#weight' => 1,
      '#open' => FALSE,
    ];
    $elements['thumbnail_slider']['general']['mode'] = [
      '#title' => $this->t('Mode'),
      '#type' => 'select',
      '#default_value' => $settings['thumbnail_slider']['general']['mode'],
      '#options' => [
        'horizontal' => 'horizontal',
        'fade' => 'fade',
      ],
    ];
    $elements['thumbnail_slider']['general']['speed'] = [
      '#title' => $this->t('Speed'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['general']['speed'],
    ];
    $elements['thumbnail_slider']['general']['slideMargin'] = [
      '#title' => $this->t('slideMargin'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['general']['slideMargin'],
    ];
    $elements['thumbnail_slider']['general']['startSlide'] = [
      '#title' => $this->t('startSlide'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['general']['startSlide'],
    ];
    $elements['thumbnail_slider']['general']['randomStart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('randomStart'),
      '#default_value' => $settings['thumbnail_slider']['general']['randomStart'],
    ];
    $elements['thumbnail_slider']['general']['infiniteLoop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('infiniteLoop'),
      '#default_value' => $settings['thumbnail_slider']['general']['infiniteLoop'],
    ];
    $elements['thumbnail_slider']['general']['hideControlOnEnd'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('hideControlOnEnd'),
      '#default_value' => $settings['thumbnail_slider']['general']['hideControlOnEnd'],
    ];
    $elements['thumbnail_slider']['general']['easing'] = [
      '#title' => $this->t('easing'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['general']['easing'],
    ];
    $elements['thumbnail_slider']['general']['captions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('captions'),
      '#default_value' => $settings['thumbnail_slider']['general']['captions'],
    ];
    $elements['thumbnail_slider']['general']['ticker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('ticker'),
      '#default_value' => $settings['thumbnail_slider']['general']['ticker'],
    ];
    $elements['thumbnail_slider']['general']['tickerHover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('tickerHover'),
      '#default_value' => $settings['thumbnail_slider']['general']['tickerHover'],
    ];
    $elements['thumbnail_slider']['general']['adaptiveHeight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('adaptiveHeight'),
      '#default_value' => $settings['thumbnail_slider']['general']['adaptiveHeight'],
    ];
    $elements['thumbnail_slider']['general']['adaptiveHeightSpeed'] = [
      '#title' => $this->t('adaptiveHeightSpeed'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['general']['adaptiveHeightSpeed'],
    ];
    $elements['thumbnail_slider']['general']['responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('responsive'),
      '#default_value' => $settings['thumbnail_slider']['general']['responsive'],
    ];
    $elements['thumbnail_slider']['general']['useCSS'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('useCSS'),
      '#default_value' => $settings['thumbnail_slider']['general']['useCSS'],
    ];
    $elements['thumbnail_slider']['general']['preloadImages'] = [
      '#title' => $this->t('preloadImages'),
      '#type' => 'select',
      '#default_value' => $settings['thumbnail_slider']['general']['preloadImages'],
      '#options' => [
        'all' => 'all',
        'visible' => 'visible',
      ],
    ];
    $elements['slider']['general']['touchEnabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('touchEnabled'),
      '#default_value' => $settings['slider']['general']['touchEnabled'],
    ];
    $elements['thumbnail_slider']['general']['swipeThreshold'] = [
      '#title' => $this->t('swipeThreshold'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['general']['swipeThreshold'],
    ];
    $elements['thumbnail_slider']['general']['oneToOneTouch'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('oneToOneTouch'),
      '#default_value' => $settings['thumbnail_slider']['general']['oneToOneTouch'],
    ];
    $elements['thumbnail_slider']['general']['preventDefaultSwipeX'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('preventDefaultSwipeX'),
      '#default_value' => $settings['thumbnail_slider']['general']['preventDefaultSwipeX'],
    ];
    $elements['thumbnail_slider']['general']['preventDefaultSwipeY'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('preventDefaultSwipeY'),
      '#default_value' => $settings['thumbnail_slider']['general']['preventDefaultSwipeY'],
    ];
    $elements['thumbnail_slider']['general']['wrapperClass'] = [
      '#title' => $this->t('wrapperClass'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['general']['wrapperClass'],
    ];

    $elements['thumbnail_slider']['pager']['pager'] = [
      '#type' => 'hidden',
      '#default_value' => $settings['thumbnail_slider']['pager']['pager'],
    ];

    $elements['thumbnail_slider']['controls'] = [
      '#type' => 'details',
      '#title' => $this->t('Controls'),
      '#weight' => 3,
      '#open' => FALSE,
    ];
    $elements['thumbnail_slider']['controls']['controls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('controls'),
      '#default_value' => $settings['thumbnail_slider']['controls']['controls'],
    ];
    $elements['thumbnail_slider']['controls']['nextText'] = [
      '#title' => $this->t('nextText'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['controls']['nextText'],
    ];
    $elements['thumbnail_slider']['controls']['prevText'] = [
      '#title' => $this->t('prevText'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['controls']['prevText'],
    ];
    $elements['thumbnail_slider']['controls']['autoControls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('autoControls'),
      '#default_value' => $settings['thumbnail_slider']['controls']['autoControls'],
    ];
    $elements['thumbnail_slider']['controls']['startText'] = [
      '#title' => $this->t('startText'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['controls']['startText'],
    ];
    $elements['thumbnail_slider']['controls']['stopText'] = [
      '#title' => $this->t('stopText'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['controls']['stopText'],
    ];
    $elements['thumbnail_slider']['controls']['autoControlsCombine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto'),
      '#default_value' => $settings['thumbnail_slider']['controls']['autoControlsCombine'],
    ];

    $elements['thumbnail_slider']['auto'] = [
      '#type' => 'details',
      '#title' => $this->t('Auto'),
      '#weight' => 4,
      '#open' => FALSE,
    ];
    $elements['thumbnail_slider']['auto']['auto'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto'),
      '#default_value' => $settings['thumbnail_slider']['auto']['auto'],
    ];
    $elements['thumbnail_slider']['auto']['pause'] = [
      '#title' => $this->t('pause'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['auto']['pause'],
    ];
    $elements['thumbnail_slider']['auto']['autoStart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('autoStart'),
      '#default_value' => $settings['thumbnail_slider']['auto']['autoStart'],
    ];
    $elements['thumbnail_slider']['auto']['autoDirection'] = [
      '#title' => $this->t('autoDirection'),
      '#type' => 'select',
      '#default_value' => $settings['thumbnail_slider']['auto']['autoDirection'],
      '#options' => [
        'next' => 'next',
        'prev' => 'prev',
      ],
    ];
    $elements['thumbnail_slider']['auto']['autoHover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('autoHover'),
      '#default_value' => $settings['thumbnail_slider']['auto']['autoHover'],
    ];
    $elements['thumbnail_slider']['auto']['autoDelay'] = [
      '#title' => $this->t('autoDelay'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['auto']['autoDelay'],
    ];

    $elements['thumbnail_slider']['carousel'] = [
      '#type' => 'details',
      '#title' => $this->t('Carousel'),
      '#weight' => 5,
      '#open' => FALSE,
    ];
    $elements['thumbnail_slider']['carousel']['minSlides'] = [
      '#title' => $this->t('minSlides'),
      '#type' => 'number',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['carousel']['minSlides'],
    ];
    $elements['thumbnail_slider']['carousel']['maxSlides'] = [
      '#title' => $this->t('maxSlides'),
      '#type' => 'number',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['carousel']['maxSlides'],
    ];
    $elements['thumbnail_slider']['carousel']['moveSlides'] = [
      '#title' => $this->t('moveSlides'),
      '#type' => 'number',
      '#size' => 60,
      '#default_value' => $settings['thumbnail_slider']['carousel']['moveSlides'],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('BxSlider (with thumbnail slider) configuration');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $parent_elements = parent::viewElements($items, $langcode);

    $elements = [];
    $settings = $this->settings;
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $image_style_setting = $settings["thumbnail_slider"]["thumbnail_style"];

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    $rendering_ths_items = [];
    foreach ($files as $delta => $file) {
      $cache_contexts = [];
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        $url = Url::fromUri(file_create_url($image_uri));
        $cache_contexts[] = 'url.site';
      }
      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      $rendering_ths_items[] = $file->_referringItem;

    }

    // A slider's own pager must be disabled, because for pager is used
    // another bxslider.
    $settings['slider']['pager']['pager'] = FALSE;

    $bxslider_settings['bxslider'] = array_merge(
      $settings['slider']['general'],
      $settings['slider']['pager'],
      $settings['slider']['controls'],
      $settings['slider']['auto'],
      $settings['slider']['carousel']
    );
    $bxslider_settings['image_style'] = $settings['slider']['image_style'];
    $bxslider_settings['slider_id'] = 'bxslider-ths-' . str_replace('_', '-', $items->getName());

    $bxslider_settings['colorbox'] = $settings['colorbox'];

    $bxslider_settings['thumbnail_slider'] = array_merge(
      $settings['thumbnail_slider']['general'],
      $settings['thumbnail_slider']['pager'],
      $settings['thumbnail_slider']['controls'],
      $settings['thumbnail_slider']['auto'],
      $settings['thumbnail_slider']['carousel']
    );
    $bxslider_settings['thumbnail_slider']['thumbnail_style'] = $settings['thumbnail_slider']['thumbnail_style'];
    // Get thumbnail's width.
    $image_style_ths = $this->imageStyleStorage->load($settings['thumbnail_slider']['thumbnail_style']);
    foreach ($image_style_ths->getEffects() as $effect) {
      $thumbnail_width = $effect->configuration['width'];
    }
    $bxslider_settings['thumbnail_slider']['slideWidth'] = $thumbnail_width;

    $element = [
      '#theme' => 'bxslider_ths',
      '#items' => $parent_elements['#items'],
      '#thumbnail_items' => $rendering_ths_items,
      '#settings' => $bxslider_settings,
      '#cache' => [
        'tags' => $cache_tags,
        'contexts' => $cache_contexts,
      ],
    ];

    // Attach library.
    $element['#attached']['library'][] = 'bxslider/jquery.bxslider_ths';

    // Attach settings.
    $this->sliderSettingsFixIntegerValues($bxslider_settings);
    $element['#attached']['drupalSettings']['bxslider_ths'][$bxslider_settings['slider_id']] = $bxslider_settings;

    return $element;
  }

}
