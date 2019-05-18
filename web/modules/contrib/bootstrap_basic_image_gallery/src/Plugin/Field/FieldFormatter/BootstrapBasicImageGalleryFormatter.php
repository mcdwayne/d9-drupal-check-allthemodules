<?php

namespace Drupal\bootstrap_basic_image_gallery\Plugin\Field\FieldFormatter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\Component\Utility\Html;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Plugin implementation of the 'BootstrapBasicImageGalleryFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "bootstrap_basic_image_gallery_formatter",
 *   label = @Translation("Bootstrap Basic Image Gallery"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class BootstrapBasicImageGalleryFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Image style storage.
   *
   * @var Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Theme manager.
   *
   * @var Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Configuration.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityStorageInterface $image_style_storage, ThemeManagerInterface $theme_manager, ConfigFactory $configFactory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->imageStyleStorage = $image_style_storage;
    $this->themeManager = $theme_manager;
    $this->configFactory = $configFactory;
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
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('theme.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return [];
    }

    // Determine the number of columns to show.
    $thumbnailsPerRow = $this->getSetting('thumbnails_per_row');
    $numberColumns = 0;
    if ($thumbnailsPerRow > 0) {
      $numberColumns = round(10 / $this->getSetting('thumbnails_per_row'));
    }
    // Build the render array to pass to the template file.
    $imageGallery = [
      '#attached' => [
        'library' => [
          // Attach the basic library for this module to provide needed css.
          'bootstrap_basic_image_gallery/bootstrap_basic',
          // Attach the library which allows previewing images on hover.
          'bootstrap_basic_image_gallery/hover_preview',
        ],
      ],
      '#theme' => 'bootstrap_basic_image_gallery',
      '#main' => [],
      '#thumbnails' => [
        'class' => 'bscol-' . $numberColumns,
        'images' => [],
      ],
      '#lazyload' => ($this->getSetting('lazyload') ? 'lazy' : ''),
      '#modal' => [
        'id' => Html::getUniqueId('bootstrap-basic-image-gallery-modal'),
        'label' => $this->fieldDefinition->getLabel(),
      ],
      '#carousel' => [
        'id' => Html::getUniqueId('bootstrap-basic-image-gallery-carousel'),
        'autoplay' => ($this->getSetting('carousel_autorotate') ? 'carousel' : 'false'),
        'interval' => $this->getSetting('carousel_interval'),
        'images' => [],
      ],
    ];

    // Load the configuration settings.
    $configuration_settings = $this->configFactory->get('bootstrap_basic_image_gallery.settings');

    if (!$configuration_settings->get('prevent_load_bootstrap')) {
      // Check to make sure the theme isn't already including bootstrap.
      $bootstrap_included = FALSE;
      foreach ($this->themeManager->getActiveTheme()->getLibraries() as $library) {
        if ($bootstrap_included = preg_match('%^bootstrap%', $library)) {
          break;
        }
      }

      // Attach the bootstrap core library if its not already included in theme.
      if (!$bootstrap_included) {
        $imageGallery['#attached']['library'][] = 'bootstrap_basic_image_gallery/bootstrap_components';
      }
    }

    // Attach the lazy load library.
    if ($this->getSetting('lazyload')) {
      $imageGallery['#attached']['library'][] = 'bootstrap_basic_image_gallery/lazyload';
    }

    // Collect cache tags to be added for each thumbnail in the field.
    $thumbnail_cache_tags = [];
    $thumbnail_image_style = $this->getSetting('thumbnail_image_style');
    if (!empty($thumbnail_image_style)) {
      $image_style = $this->imageStyleStorage->load($thumbnail_image_style);
      $thumbnail_cache_tags = $image_style->getCacheTags();
    }

    // Get the main image style.
    $main_image_style_setting = $this->getSetting('image_style');
    if (!empty($main_image_style_setting)) {
      $main_image_style = $this->imageStyleStorage->load($main_image_style_setting);
    }

    // Get the modal image style.
    $modal_image_style_setting = $this->getSetting('modal_image_style');
    if (!empty($modal_image_style_setting)) {
      $modal_image_style = $this->imageStyleStorage->load($modal_image_style_setting);
    }

    // Loop over the files and render them.
    foreach ($files as $delta => $file) {

      $image_uri = $file->getFileUri();
      $url = Url::fromUri(file_create_url($image_uri));

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      // Create the main image container.
      if ($delta == 0) {
        // Collect cache tags to be added for each item in the field.
        $cache_tags = [];
        if (isset($main_image_style)) {
          $cache_tags = $main_image_style->getCacheTags();
        }
        $modal_cache_tags = [];
        if (isset($modal_image_style)) {
          $modal_cache_tags = $modal_image_style->getCacheTags();
        }

        $imageGallery['#main'] = [
          '#theme' => 'image_formatter',
          '#item' => $item,
          '#item_attributes' => $item_attributes,
          '#image_style' => $main_image_style_setting,
          '#cache' => [
            'tags' => Cache::mergeTags($cache_tags, $file->getCacheTags()),
          ],
        ];
      }

      // Add the main image as a data source for hover swapping.
      if (isset($main_image_style)) {
        $item_attributes['data-mainsrc'] = $main_image_style->buildUrl($image_uri);
      }
      else {
        $item_attributes['data-mainsrc'] = file_create_url($image_uri);
      }

      // Generate the thumbnail.
      if ($thumbnailsPerRow > 0) {
        $imageGallery['#thumbnails']['images'][] = [
          '#theme' => 'image_formatter',
          '#item' => $item,
          '#item_attributes' => $item_attributes,
          '#image_style' => $thumbnail_image_style,
          '#cache' => [
            'tags' => Cache::mergeTags($thumbnail_cache_tags, $file->getCacheTags()),
          ],
        ];
      }

      // Add the carousel image container.
      $imageGallery['#carousel']['images'][$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $modal_image_style_setting,
        '#caption' => $item->__get('alt'),
        '#cache' => [
          'tags' => Cache::mergeTags($modal_cache_tags, $file->getCacheTags()),
        ],
      ];
      // Pass image differently depending on if we are lazy loading.
      if ($this->getSetting('lazyload')) {
        $imageGallery['#carousel']['images'][$delta]['#attributes']['data-src'] = $url->toString();
      }
      else {
        $imageGallery['#carousel']['images'][$delta]['#uri'] = $image_uri;
      }
    }

    return [$imageGallery];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Image Style: <strong>@settingValue</strong>', [
      '@settingValue' => (empty($this->getSetting('image_style')) ? 'Original Image' : $this->getSetting('image_style')),
    ]);
    $summary[] = $this->t('Thumbnails Per Row: <strong>@settingValue</strong>', [
      '@settingValue' => $this->getSetting('thumbnails_per_row'),
    ]);
    $summary[] = $this->t('Thumbnail Image Style: <strong>@settingValue</strong>', [
      '@settingValue' => (empty($this->getSetting('thumbnail_image_style')) ? 'Original Image' : $this->getSetting('thumbnail_image_style')),
    ]);
    $summary[] = $this->t('Modal Image Style: <strong>@settingValue</strong>', [
      '@settingValue' => (empty($this->getSetting('modal_image_style')) ? 'Original Image' : $this->getSetting('modal_image_style')),
    ]);
    $summary[] = $this->t('Autorotate Carousel? <strong>@settingValue</strong>', [
      '@settingValue' => ($this->getSetting('carousel_autorotate') ? 'Yes' : 'No'),
    ]);
    if ($this->getSetting('carousel_autorotate')) {
      $summary[] = $this->t('Autorotate Carousel Interval <strong>@settingValue</strong>', [
        '@settingValue' => (empty($this->getSetting('carousel_interval')) ? 5000 : $this->getSetting('carousel_interval')),
      ]);
    }
    $summary[] = $this->t('Lazy Load Images? <strong>@settingValue</strong>', [
      '@settingValue' => ($this->getSetting('lazyload') ? 'Yes' : 'No'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);

    $elements = [];
    $elements['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $this->t('Image style used for rendering the main image.'),
    ];
    $elements['thumbnails_per_row'] = [
      '#type' => 'number',
      '#title' => $this->t('Thumbnails Per Row'),
      '#description' => $this->t('Number of thumbnails displayed per row under the main image.'),
      '#min' => 0,
      '#default_value' => $this->getSetting('thumbnails_per_row'),
    ];
    $elements['thumbnail_image_style'] = [
      '#title' => $this->t('Thumbnail Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('thumbnail_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $this->t('Image style used for rendering the thumbnails.'),
    ];
    $elements['modal_image_style'] = [
      '#title' => $this->t('Modal Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('modal_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $this->t('Image style used for rendering the image in the modal popup (on click).'),
    ];
    $elements['carousel_autorotate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autorotate Carousel?'),
      '#description' => $this->t('Decides whether or not the carousel auto-rotates after opening.'),
      '#default_value' => $this->getSetting('carousel_autorotate'),
    ];
    $elements['carousel_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Autorotate Carousel Interval'),
      '#description' => $this->t('The amount of time to delay (in milliseconds) between automatically cycling an image.'),
      '#default_value' => $this->getSetting('carousel_interval'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][carousel_autorotate]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['lazyload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Lazy Load Images?'),
      '#description' => $this->t('Decides whether or not the images in the popup will be lazy loaded. If yes, the images will not be loaded by the user until they are viewed. This speeds up page loading time.'),
      '#default_value' => $this->getSetting('lazyload'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'thumbnail_image_style' => '',
      'thumbnails_per_row' => '3',
      'modal_image_style' => '',
      'carousel_autorotate' => 0,
      'carousel_interval' => 5000,
      'lazyload' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    foreach (['image_style', 'thumbnail_image_style', 'modal_image_style'] as $setting_name) {
      $style_id = $this->getSetting($setting_name);
      /** @var \Drupal\image\ImageStyleInterface $style */
      if ($style_id && $style = ImageStyle::load($style_id)) {
        // If this formatter uses a valid image style to display the image, add
        // the image style configuration entity as dependency of this formatter.
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    foreach (['image_style', 'thumbnail_image_style', 'modal_image_style'] as $setting_name) {
      $style_id = $this->getSetting($setting_name);
      /** @var \Drupal\image\ImageStyleInterface $style */
      if ($style_id && $style = ImageStyle::load($style_id)) {
        if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
          $replacement_id = $this->imageStyleStorage->getReplacementId($style_id);
          // If a valid replacement has been provided in the storage, replace
          // the image style with the replacement and signal that the formatter
          // plugin settings were updated.
          if ($replacement_id && ImageStyle::load($replacement_id)) {
            $this->setSetting($setting_name, $replacement_id);
            $changed = TRUE;
          }
        }
      }
    }
    return $changed;
  }

}
