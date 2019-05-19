<?php

namespace Drupal\masonry\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Wrapper methods for Masonry API methods.
 *
 *
 * @ingroup masonry
 */
class MasonryService {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs a MasonryService object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   *
   */
  function __construct(ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager) {
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * Get default Masonry options.
   *
   * @return
   *   An associative array of default options for Masonry.
   *   Contains:
   *   - masonry_column_width: The width of each column (in pixels or as a
   *     percentage).
   *   - masonry_column_width_units: The units to use for the column width
   *   ('px'
   *     or '%').
   *   - masonry_gutter_width: The spacing between each column (in pixels).
   *   - masonry_resizable: Automatically rearrange items when the container is
   *     resized.
   *   - masonry_animated: Animate item rearrangements.
   *   - masonry_animation_duration: The duration of animations (in
   *   milliseconds).
   *   - masonry_fit_width: Sets the width of the container to the nearest
   *   column. Ideal for centering Masonry layouts.
   *   - masonry_rtl: Display items from right-to-left.
   *   - masonry_images_first: Load all images first before triggering Masonry.
   */
  public function getMasonryDefaultOptions() {
    return [
      'layoutColumnWidth' => '',
      'layoutColumnWidthUnit' => 'px',
      'gutterWidth' => '0',
      'isLayoutResizable' => TRUE,
      'isLayoutAnimated' => TRUE,
      'layoutAnimationDuration' => '500',
      'isLayoutFitsWidth' => FALSE,
      'isLayoutRtlMode' => FALSE,
      'isLayoutImagesLoadedFirst' => TRUE,
      'stampSelector' => '',
      'isItemsPositionInPercent' => FALSE,
    ];
  }

  /**
   * Apply Masonry to a container.
   *
   * @param $container
   *   The CSS selector of the container element to apply Masonry to.
   * @param $options
   *   An associative array of Masonry options.
   *   Contains:
   *   - masonry_item_selector: The CSS selector of the items within the
   *     container.
   *   - masonry_column_width: The width of each column (in pixels or as a
   *     percentage).
   *   - masonry_column_width_units: The units to use for the column width
   *   ('px'
   *     or '%').
   *   - masonry_gutter_width: The spacing between each column (in pixels).
   *   - masonry_resizable: Automatically rearrange items when the container is
   *     resized.
   *   - masonry_animated: Animate item rearrangements.
   *   - masonry_animation_duration: The duration of animations (in
   *   milliseconds).
   *   - masonry_fit_width: Sets the width of the container to the nearest
   *   column. Ideal for centering Masonry layouts.
   *   - masonry_rtl: Display items from right-to-left.
   *   - masonry_images_first: Load all images first before triggering Masonry.
   * @param array
   *   Some IDs to target this particular display in
   *   hook_masonry_script_alter().
   */
  public function applyMasonryDisplay(&$form, $container, $item_selector, $options = [], $masonry_ids = ['masonry_default']) {

    //if (masonry_loaded() && !empty($container)) {
    if (!empty($container)) {
      // For any options not specified, use default options
      $options += $this->getMasonryDefaultOptions();
      if (!isset($item_selector)) {
        $item_selector = '';
      }

      // Setup Masonry script
      $masonry = [
        'masonry' => [
          $container => [
            'masonry_ids' => $masonry_ids,
            'item_selector' => $item_selector,
            'column_width' => $options['layoutColumnWidth'],
            'column_width_units' => $options['layoutColumnWidthUnit'],
            'gutter_width' => (int) $options['gutterWidth'],
            'resizable' => (bool) $options['isLayoutResizable'],
            'animated' => (bool) $options['isLayoutAnimated'],
            'animation_duration' => (int) $options['layoutAnimationDuration'],
            'fit_width' => (bool) $options['isLayoutFitsWidth'],
            'rtl' => (bool) $options['isLayoutRtlMode'],
            'images_first' => (bool) $options['isLayoutImagesLoadedFirst'],
            'stamp' => $options['stampSelector'],
            'percent_position' => (bool) $options['isItemsPositionInPercent'],
          ],
        ],
      ];

      // Allow other modules and themes to alter the settings.
      $this->moduleHandler->alter('masonry_script', $masonry);
      $this->themeManager->alter('masonry_script', $masonry);

      $form['#attached']['library'][] = 'masonry/masonry.layout';
      $form['#attached']['drupalSettings'] = $masonry;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm($default_values = []) {

    // Load module default values if empty.
    if (empty($default_values)) {
      $default_values = $this->getMasonryDefaultOptions();
    }

    $form['layoutColumnWidth'] = [
      '#type' => 'textfield',
      '#title' => t('Column width'),
      '#description' => t("The width of each column, enter pixels, percentage, or string of css selector"),
      '#default_value' => $default_values['layoutColumnWidth'],
    ];
    $form['layoutColumnWidthUnit'] = [
      '#type' => 'radios',
      '#title' => t('Column width units'),
      '#description' => t("The units to use for the column width."),
      '#options' => [
        'px' => t("Pixels"),
        '%' => t("Percentage (of container's width)"),
        'css' => t("CSS selector (you must configure your css to set widths for .masonry-item)"),
      ],
      '#default_value' => $default_values['layoutColumnWidthUnit'],
    ];
    $form['gutterWidth'] = [
      '#type' => 'textfield',
      '#title' => t('Gutter width'),
      '#description' => t("The spacing between each column."),
      '#default_value' => $default_values['gutterWidth'],
      '#size' => 4,
      '#maxlength' => 3,
      '#field_suffix' => t('px'),
    ];
    $form['stampSelector'] = [
      '#type' => 'textfield',
      '#title' => t('Stamp Selector'),
      '#description' => t("Specifies which elements are stamped within the layout using css selector"),
      '#default_value' => $default_values['stampSelector'],
    ];
    $form['isLayoutResizable'] = [
      '#type' => 'checkbox',
      '#title' => t('Resizable'),
      '#description' => t("Automatically rearrange items when the container is resized."),
      '#default_value' => $default_values['isLayoutResizable'],
    ];
    $form['isLayoutAnimated'] = [
      '#type' => 'checkbox',
      '#title' => t('Animated'),
      '#description' => t("Animate item rearrangements."),
      '#default_value' => $default_values['isLayoutAnimated'],
      '#states' => [
        'visible' => [
          'input.form-checkbox[name*="isLayoutResizable"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['layoutAnimationDuration'] = [
      '#type' => 'textfield',
      '#title' => t('Animation duration'),
      '#description' => t("The duration of animations (1000 ms = 1 sec)."),
      '#default_value' => $default_values['layoutAnimationDuration'],
      '#size' => 5,
      '#maxlength' => 4,
      '#field_suffix' => t('ms'),
      '#states' => [
        'visible' => [
          'input.form-checkbox[name*="isLayoutResizable"]' => ['checked' => TRUE],
          'input.form-checkbox[name*="isLayoutAnimated"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['isLayoutFitsWidth'] = [
      '#type' => 'checkbox',
      '#title' => t('Fit width'),
      '#description' => t("Sets the width of the container to the nearest column. Ideal for centering Masonry layouts. See the <a href='http://masonry.desandro.com/demos/centered.html'>'Centered' demo</a> for more information."),
      '#default_value' => $default_values['isLayoutFitsWidth'],
    ];
    $form['isLayoutRtlMode'] = [
      '#type' => 'checkbox',
      '#title' => t('RTL layout'),
      '#description' => t("Display items from right-to-left."),
      '#default_value' => $default_values['isLayoutRtlMode'],
    ];
    $form['isLayoutImagesLoadedFirst'] = [
      '#type' => 'checkbox',
      '#title' => t('Load images first'),
      '#description' => t("Load all images first before triggering Masonry."),
      '#default_value' => $default_values['isLayoutImagesLoadedFirst'],
    ];
    $form['isItemsPositionInPercent'] = [
      '#type' => 'checkbox',
      '#title' => t('Percent position'),
      '#description' => t("Sets item positions in percent values, rather than pixel values. Checking this will works well with percent-width items, as items will not transition their position on resize. See the <a href='http://masonry.desandro.com/options.html#percentposition'>masonry doc</a> for more information."),
      '#default_value' => $default_values['isItemsPositionInPercent'],
    ];

    // Allow other modules and themes to alter the form.
    $this->moduleHandler->alter('masonry_options_form', $form, $default_values);
    $this->themeManager->alter('masonry_options_form', $form, $default_values);

    return $form;
  }

  /**
   * Check if the Masonry and imagesLoaded libraries are installed.
   *
   * @return
   *   A boolean indicating the installed status.
   */
  function isMasonryInstalled() {
    $path = DRUPAL_ROOT . '/libraries/masonry/masonry.pkgd.min.js';
    if (\Drupal::moduleHandler()->moduleExists('libraries')) {
      $path = libraries_get_path('masonry') . '/masonry.pkgd.min.js';
    }

    // Is the library found in the root libraries path.
    $library_found = file_exists($path);

    // If library is not found, then look in the current profile libraries path.
    if (!$library_found) {
      $profile_path = drupal_get_path('profile', drupal_get_profile());
      $profile_path .= '/libraries/masonry.pkgd.min.js';
      // Is the library found in the current profile libraries path.
      $library_found = file_exists($profile_path);
    }

    return $library_found;
  }
}
