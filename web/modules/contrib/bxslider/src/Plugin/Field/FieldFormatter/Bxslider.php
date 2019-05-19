<?php

namespace Drupal\bxslider\Plugin\Field\FieldFormatter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Cache\Cache;

/**
 * BxSlider.
 *
 * @FieldFormatter(
 *  id = "bxslider",
 *  label = @Translation("BxSlider"),
 *  field_types = {"image", "media"}
 * )
 */
class Bxslider extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The entity storage for the image.
   */
  public function __construct($plugin_id,
                              $plugin_definition,
                              FieldDefinitionInterface $field_definition,
                              array $settings,
                              $label,
                              $view_mode,
                              array $third_party_settings,
                              EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'slider' => [
        'image_style' => 'large',
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
          'pager' => TRUE,
          'pagerType' => 'full',
          'pagerShortSeparator' => ' / ',
          'pagerSelector' => '',
          'pagerCustom_type' => 'none',
        // 'pagerCustom' => 'null',.
          'pagerCustom_image_style' => 'thumbnail',
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
          'minSlides' => 1,
          'maxSlides' => 1,
          'moveSlides' => 0,
          'slideWidth' => 0,
        ],
      ],
      'colorbox' => [
        'enable' => FALSE,
        'image_style' => 'large',
        'colorbox_gallery' => 'none',
        'colorbox_gallery_custom' => '',
        'colorbox_caption' => 'none',
        'colorbox_caption_custom' => '',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();
    $field_name = $this->fieldDefinition->getName();

    $image_styles = image_style_options(FALSE);

    $elements['slider'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('BxSlider'),
      '#weight' => 0,
    ];

    $elements['slider']['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $settings['slider']['image_style'],
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
    ];

    $elements['slider']['description'] = [
      '#markup' => $this->t('Visit <a href="@field-help" target="_blank">http://bxslider.com/options</a> for more information about bxSlider options.', ['@field-help' => 'http://bxslider.com/options']),
    ];

    $elements['slider']['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#weight' => 1,
      '#open' => FALSE,
    ];
    $elements['slider']['general']['mode'] = [
      '#title' => $this->t('Mode'),
      '#type' => 'select',
      '#default_value' => $settings['slider']['general']['mode'],
      '#options' => [
        'horizontal' => 'horizontal',
        'vertical' => 'vertical',
        'fade' => 'fade',
      ],
    ];
    $elements['slider']['general']['speed'] = [
      '#title' => $this->t('Speed'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['general']['speed'],
    ];
    $elements['slider']['general']['slideMargin'] = [
      '#title' => $this->t('slideMargin'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['general']['slideMargin'],
    ];
    $elements['slider']['general']['startSlide'] = [
      '#title' => $this->t('startSlide'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['general']['startSlide'],
    ];
    $elements['slider']['general']['randomStart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('randomStart'),
      '#default_value' => $settings['slider']['general']['randomStart'],
    ];
    $elements['slider']['general']['infiniteLoop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('infiniteLoop'),
      '#default_value' => $settings['slider']['general']['infiniteLoop'],
    ];
    $elements['slider']['general']['hideControlOnEnd'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('hideControlOnEnd'),
      '#default_value' => $settings['slider']['general']['hideControlOnEnd'],
    ];
    $elements['slider']['general']['easing'] = [
      '#title' => $this->t('easing'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['general']['easing'],
    ];
    $elements['slider']['general']['captions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('captions'),
      '#default_value' => $settings['slider']['general']['captions'],
    ];
    $elements['slider']['general']['ticker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('ticker'),
      '#default_value' => $settings['slider']['general']['ticker'],
    ];
    $elements['slider']['general']['tickerHover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('tickerHover'),
      '#default_value' => $settings['slider']['general']['tickerHover'],
    ];
    $elements['slider']['general']['adaptiveHeight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('adaptiveHeight'),
      '#default_value' => $settings['slider']['general']['adaptiveHeight'],
    ];
    $elements['slider']['general']['adaptiveHeightSpeed'] = [
      '#title' => $this->t('adaptiveHeightSpeed'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['general']['adaptiveHeightSpeed'],
    ];
    $elements['slider']['general']['video'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('video'),
      '#default_value' => $settings['slider']['general']['video'],
    ];
    $elements['slider']['general']['responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('responsive'),
      '#default_value' => $settings['slider']['general']['responsive'],
    ];
    $elements['slider']['general']['useCSS'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('useCSS'),
      '#default_value' => $settings['slider']['general']['useCSS'],
    ];
    $elements['slider']['general']['preloadImages'] = [
      '#title' => $this->t('preloadImages'),
      '#type' => 'select',
      '#default_value' => $settings['slider']['general']['preloadImages'],
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
    $elements['slider']['general']['swipeThreshold'] = [
      '#title' => $this->t('swipeThreshold'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['general']['swipeThreshold'],
    ];
    $elements['slider']['general']['oneToOneTouch'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('oneToOneTouch'),
      '#default_value' => $settings['slider']['general']['oneToOneTouch'],
    ];
    $elements['slider']['general']['preventDefaultSwipeX'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('preventDefaultSwipeX'),
      '#default_value' => $settings['slider']['general']['preventDefaultSwipeX'],
    ];
    $elements['slider']['general']['preventDefaultSwipeY'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('preventDefaultSwipeY'),
      '#default_value' => $settings['slider']['general']['preventDefaultSwipeY'],
    ];
    $elements['slider']['general']['wrapperClass'] = [
      '#title' => $this->t('wrapperClass'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['general']['wrapperClass'],
    ];

    $elements['slider']['pager'] = [
      '#type' => 'details',
      '#title' => $this->t('Pager'),
      '#weight' => 2,
      '#open' => FALSE,
    ];
    $elements['slider']['pager']['pager'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('pager'),
      '#default_value' => $settings['slider']['pager']['pager'],
    ];
    $elements['slider']['pager']['pagerType'] = [
      '#title' => $this->t('pagerType'),
      '#type' => 'select',
      '#default_value' => $settings['slider']['pager']['pagerType'],
      '#options' => [
        'full' => 'full',
        'short' => 'short',
      ],
      '#states' => [
        'enabled' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][slider][pager][pager]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['slider']['pager']['pagerShortSeparator'] = [
      '#title' => $this->t('pagerShortSeparator'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['pager']['pagerShortSeparator'],
      '#states' => [
        'enabled' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][slider][pager][pager]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['slider']['pager']['pagerSelector'] = [
      '#title' => $this->t('pagerSelector'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['pager']['pagerSelector'],
      '#states' => [
        'enabled' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][slider][pager][pager]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['slider']['pager']['pagerCustom_type_markup'] = [
      '#markup' => '<hr>',
    ];
    $elements['slider']['pager']['pagerCustom_type'] = [
      '#title' => $this->t('Custom Pager'),
      '#type' => 'select',
      '#default_value' => $settings['slider']['pager']['pagerCustom_type'],
      '#options' => [
        'none' => 'None',
        'thumbnail_pager_method1' => 'Custom thumbnail pager - method 1',
        'thumbnail_pager_method2' => 'Custom thumbnail pager - method 2',
      ],
      '#description' => $this->t('Select a predefined custom thumbnail pager.'),
      '#states' => [
        'enabled' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][slider][pager][pager]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['slider']['pager']['pagerCustom_image_style'] = [
      '#title' => $this->t('Custom Pager - Image style'),
      '#type' => 'select',
      '#default_value' => $settings['slider']['pager']['pagerCustom_image_style'],
      '#empty_option' => $this->t('None (thumbnail)'),
      '#options' => $image_styles,
      '#description' => $this->t('Used only when some the "Custom Pager" option is selected.'),
      '#states' => [
        'enabled' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][slider][pager][pagerCustom_type]"]' => [
            ['value' => 'thumbnail_pager_method1'],
            ['value' => 'thumbnail_pager_method2'],
          ],
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][slider][pager][pager]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['slider']['controls'] = [
      '#type' => 'details',
      '#title' => $this->t('Controls'),
      '#weight' => 3,
      '#open' => FALSE,
    ];
    $elements['slider']['controls']['controls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('controls'),
      '#default_value' => $settings['slider']['controls']['controls'],
    ];
    $elements['slider']['controls']['nextText'] = [
      '#title' => $this->t('nextText'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['controls']['nextText'],
    ];
    $elements['slider']['controls']['prevText'] = [
      '#title' => $this->t('prevText'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['controls']['prevText'],
    ];
    $elements['slider']['controls']['nextSelector'] = [
      '#title' => $this->t('nextSelector'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['controls']['nextSelector'],
    ];
    $elements['slider']['controls']['prevSelector'] = [
      '#title' => $this->t('prevSelector'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['controls']['prevSelector'],
    ];
    $elements['slider']['controls']['autoControls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('autoControls'),
      '#default_value' => $settings['slider']['controls']['autoControls'],
    ];
    $elements['slider']['controls']['startText'] = [
      '#title' => $this->t('startText'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['controls']['startText'],
    ];
    $elements['slider']['controls']['stopText'] = [
      '#title' => $this->t('stopText'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['controls']['stopText'],
    ];
    $elements['slider']['controls']['autoControlsCombine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto'),
      '#default_value' => $settings['slider']['controls']['autoControlsCombine'],
    ];
    $elements['slider']['controls']['autoControlsSelector'] = [
      '#title' => $this->t('autoControlsSelector'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['controls']['autoControlsSelector'],
    ];

    $elements['slider']['auto'] = [
      '#type' => 'details',
      '#title' => $this->t('Auto'),
      '#weight' => 4,
      '#open' => FALSE,
    ];
    $elements['slider']['auto']['auto'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto'),
      '#default_value' => $settings['slider']['auto']['auto'],
    ];
    $elements['slider']['auto']['pause'] = [
      '#title' => $this->t('pause'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['auto']['pause'],
    ];
    $elements['slider']['auto']['autoStart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('autoStart'),
      '#default_value' => $settings['slider']['auto']['autoStart'],
    ];
    $elements['slider']['auto']['autoDirection'] = [
      '#title' => $this->t('autoDirection'),
      '#type' => 'select',
      '#default_value' => $settings['slider']['auto']['autoDirection'],
      '#options' => [
        'next' => 'next',
        'prev' => 'prev',
      ],
    ];
    $elements['slider']['auto']['autoHover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('autoHover'),
      '#default_value' => $settings['slider']['auto']['autoHover'],
    ];
    $elements['slider']['auto']['autoDelay'] = [
      '#title' => $this->t('autoDelay'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['auto']['autoDelay'],
    ];

    $elements['slider']['carousel'] = [
      '#type' => 'details',
      '#title' => $this->t('Carousel'),
      '#weight' => 5,
      '#open' => FALSE,
    ];
    $elements['slider']['carousel']['minSlides'] = [
      '#title' => $this->t('minSlides'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['carousel']['minSlides'],
    ];
    $elements['slider']['carousel']['maxSlides'] = [
      '#title' => $this->t('maxSlides'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['carousel']['maxSlides'],
    ];
    $elements['slider']['carousel']['moveSlides'] = [
      '#title' => $this->t('moveSlides'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['carousel']['moveSlides'],
    ];
    $elements['slider']['carousel']['slideWidth'] = [
      '#title' => $this->t('slideWidth'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $settings['slider']['carousel']['slideWidth'],
    ];

    // $colorbox_exist = module_exists('colorbox');.
    $colorbox_exist = \Drupal::moduleHandler()->moduleExists('colorbox');
    $elements['colorbox'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Colorbox'),
      '#weight' => 11,
      '#description' => ($colorbox_exist) ? $this->t('For changing Colorbox styles and other options, please visit <a href=":link">:link</a>.', [':link' => '/admin/config/media/colorbox']) : $this->t("Please, enable the Colorbox module firstly."),
    ];
    if ($colorbox_exist) {
      $elements['colorbox']['enable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Colorbox enable'),
        '#default_value' => $settings['colorbox']['enable'],
        '#disabled' => ($colorbox_exist) ? FALSE : TRUE,
      ];
      $elements['colorbox']['image_style'] = [
        '#title' => $this->t('Colorbox Image style'),
        '#type' => 'select',
        '#default_value' => $settings['colorbox']['image_style'],
        '#empty_option' => $this->t('None (original image)'),
        '#options' => $image_styles,
        '#disabled' => $colorbox_exist ? FALSE : TRUE,
      ];
      $gallery = [
        'none' => $this->t('No gallery'),
        'field_post' => $this->t('Per field'),
        'custom' => $this->t('Custom (with tokens)'),
      ];
      $elements['colorbox']['colorbox_gallery'] = [
        '#title' => $this->t('Gallery (image grouping)'),
        '#type' => 'select',
        '#default_value' => $settings['colorbox']['colorbox_gallery'],
        '#options' => $gallery,
        '#description' => $this->t('How Colorbox should group the image galleries.'),
        '#disabled' => $colorbox_exist ? FALSE : TRUE,
      ];
      $elements['colorbox']['colorbox_gallery_custom'] = [
        '#title' => $this->t('Custom gallery'),
        '#type' => 'textfield',
        '#default_value' => $settings['colorbox']['colorbox_gallery_custom'],
        '#description' => $this->t('All images on a page with the same gallery value (rel attribute) will be grouped together. It must only contain lowercase letters, numbers, and underscores.'),
        '#required' => FALSE,
        '#states' => [
          'visible' => [
            ':input[name$="[settings_edit_form][settings][colorbox][colorbox_gallery]"]' => ['value' => 'custom'],
          ],
        ],
        '#disabled' => $colorbox_exist ? FALSE : TRUE,
      ];
      if (\Drupal::moduleHandler()->moduleExists('token')) {
        $elements['colorbox']['colorbox_token_gallery'] = [
          '#type' => 'details',
          '#title' => $this->t('Replacement patterns'),
          '#theme' => 'token_tree_link',
          '#token_types' => [$form['#entity_type'], 'file'],
          '#states' => [
            'visible' => [
              ':input[name$="[settings_edit_form][settings][colorbox][colorbox_gallery]"]' => ['value' => 'custom'],
            ],
          ],
          '#disabled' => $colorbox_exist ? FALSE : TRUE,
        ];
      }
      else {
        $elements['colorbox']['colorbox_token_gallery'] = [
          '#type' => 'details',
          '#title' => $this->t('Replacement patterns'),
          '#description' => '<strong class="error">' . $this->t('For token support the <a href="@token_url">token module</a> must be installed.', ['@token_url' => 'http://drupal.org/project/token']) . '</strong>',
          '#states' => [
            'visible' => [
              ':input[name$="[settings_edit_form][settings][colorbox][colorbox_gallery]"]' => ['value' => 'custom'],
            ],
          ],
          '#disabled' => $colorbox_exist ? FALSE : TRUE,
        ];
      }
      $caption = [
        'none' => $this->t('None'),
        'auto' => $this->t('Automatic'),
        'title' => $this->t('Title text'),
        'alt' => $this->t('Alt text'),
        'entity_title' => $this->t('Content title'),
        'custom' => $this->t('Custom (with tokens)'),
      ];
      $elements['colorbox']['colorbox_caption'] = [
        '#title' => $this->t('Caption'),
        '#type' => 'select',
        '#default_value' => $settings['colorbox']['colorbox_caption'],
        '#options' => $caption,
        '#description' => $this->t('Automatic will use the first non-empty value out of the title, the alt text and the content title.'),
        '#disabled' => $colorbox_exist ? FALSE : TRUE,
      ];
      $elements['colorbox']['colorbox_caption_custom'] = [
        '#title' => $this->t('Custom caption'),
        '#type' => 'textfield',
        '#default_value' => $settings['colorbox']['colorbox_caption_custom'],
        '#states' => [
          'visible' => [
            ':input[name$="[settings_edit_form][settings][colorbox][colorbox_caption]"]' => ['value' => 'custom'],
          ],
        ],
        '#disabled' => $colorbox_exist ? FALSE : TRUE,
      ];
      if (\Drupal::moduleHandler()->moduleExists('token')) {
        $elements['colorbox']['colorbox_token_caption'] = [
          '#type' => 'details',
          '#title' => $this->t('Replacement patterns'),
          '#theme' => 'token_tree_link',
          '#token_types' => [$form['#entity_type'], 'file'],
          '#states' => [
            'visible' => [
              ':input[name$="[settings_edit_form][settings][colorbox][colorbox_caption]"]' => ['value' => 'custom'],
            ],
          ],
          '#disabled' => $colorbox_exist ? FALSE : TRUE,
        ];
      }
      else {
        $elements['colorbox']['colorbox_token_caption'] = [
          '#type' => 'details',
          '#title' => $this->t('Replacement patterns'),
          '#description' => '<strong class="error">' . $this->t('For token support the <a href="@token_url">token module</a> must be installed.', ['@token_url' => 'http://drupal.org/project/token']) . '</strong>',
          '#states' => [
            'visible' => [
              ':input[name$="[settings_edit_form][settings][colorbox][colorbox_caption]"]' => ['value' => 'custom'],
            ],
          ],
          '#disabled' => $colorbox_exist ? FALSE : TRUE,
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('BxSlider configuration');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->settings;
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $image_style_setting = $settings["slider"]["image_style"];

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    $rendering_items = [];
    foreach ($files as $delta => $file) {
      $cache_contexts = [];
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        $url = Url::fromUri(file_create_url($image_uri));
        $cache_contexts[] = 'url.site';
      }
      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $rendering_items[$delta]['item'] = $item;
      $rendering_items[$delta]['item_attributes'] = $item_attributes;
      $rendering_items[$delta]['entity'] = $items->getEntity();
    }

    // BxSlider settings must be flat (on one level).
    $bxslider_settings['bxslider'] = array_merge(
      $settings['slider']['general'],
      $settings['slider']['pager'],
      $settings['slider']['controls'],
      $settings['slider']['auto'],
      $settings['slider']['carousel']
    );
    $bxslider_settings['image_style'] = $settings['slider']['image_style'];
    $bxslider_settings['slider_id'] = 'bxslider-' . str_replace('_', '-', $items->getName());

    $bxslider_settings['colorbox'] = $settings['colorbox'];

    $element = [
      '#theme' => 'bxslider',
      '#items' => $rendering_items,
      '#settings' => $bxslider_settings,
      '#cache' => [
        'tags' => $cache_tags,
        'contexts' => $cache_contexts,
      ],
    ];

    // Attach library.
    $element['#attached']['library'][] = 'bxslider/jquery.bxslider';

    // Attach settings.
    $this->sliderSettingsFixIntegerValues($bxslider_settings);
    $element['#attached']['drupalSettings']['bxslider'][$bxslider_settings['slider_id']] = $bxslider_settings;

    return $element;
  }

  /**
   * Replace strings with digits to int.
   */
  protected function sliderSettingsFixIntegerValues(&$settings) {
    array_walk_recursive($settings, function (&$value) {
      if (is_numeric($value)) {
        $value = (int) $value;
      }
    });
  }

}
