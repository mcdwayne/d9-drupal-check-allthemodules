<?php

namespace Drupal\aframe\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\aframe\AFrameComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'aframe_curvedimage' formatter.
 *
 * @FieldFormatter(
 *   id = "aframe_curvedimage",
 *   label = @Translation("A-Frame Curved Image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class AFrameCurvedImageFormatter extends ImageFormatter implements ContainerFactoryPluginInterface {

  use AFrameFormatterTrait;

  /**
   * The AFrame component manager.
   *
   * @var \Drupal\aframe\AFrameComponentPluginManager
   */
  protected $componentManager;

   /**
   * Constructs an AFrameCurvedImageFormatter object.
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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\aframe\AFrameComponentPluginManager $component_manager
   *   The AFrame component manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, AFrameComponentPluginManager $component_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->componentManager = $component_manager;
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
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('plugin.manager.aframe.component')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();
    unset($defaults['image_link']);

    $defaults['aframe_curvedimage_height_ratio'] = 1;
    $defaults['aframe_curvedimage_opacity'] = 1;
    $defaults['aframe_curvedimage_radius'] = 2;
    $defaults['aframe_curvedimage_segments_radial'] = 48;
    $defaults['aframe_curvedimage_theta_length'] = 360;
    $defaults['aframe_curvedimage_theta_start'] = 0;
    $defaults['aframe_curvedimage_transparent'] = TRUE;

    // Get A-Frame global formatter settings defaults.
    $defaults += AFrameFormatterTrait::globalDefaultSettings();

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    unset($element['image_link']);

    $element['aframe_curvedimage_height_ratio'] = [
      '#title'       => t('Height ratio'),
      '#type'        => 'number',
      '#description' => t('Height pixel to centimeter (cm) conversion ratio.'),
      '#value'       => $this->getSetting('aframe_curvedimage_height_ratio'),
      '#required'    => TRUE,
    ];

    $element['aframe_curvedimage_opacity'] = [
      '#title'    => t('Opacity'),
      '#type'     => 'number',
      '#step'     => 0.05,
      '#value'    => $this->getSetting('aframe_curvedimage_opacity'),
      '#required' => TRUE,
    ];

    $element['aframe_curvedimage_radius'] = [
      '#title'    => t('Radius'),
      '#type'     => 'number',
      '#step'     => 0.05,
      '#value'    => $this->getSetting('aframe_curvedimage_radius'),
      '#required' => TRUE,
    ];

    $element['aframe_curvedimage_segments_radial'] = [
      '#title'    => t('Segments radial'),
      '#type'     => 'number',
      '#step'     => 1,
      '#value'    => $this->getSetting('aframe_curvedimage_segments_radial'),
      '#required' => TRUE,
    ];

    $element['aframe_curvedimage_theta_length'] = [
      '#title'    => t('Theta length'),
      '#type'     => 'number',
      '#step'     => 0.05,
      '#value'    => $this->getSetting('aframe_curvedimage_theta_length'),
      '#required' => TRUE,
    ];

    $element['aframe_curvedimage_theta_start'] = [
      '#title'    => t('Theta start'),
      '#type'     => 'number',
      '#step'     => 0.05,
      '#value'    => $this->getSetting('aframe_curvedimage_theta_start'),
      '#required' => TRUE,
    ];

    $element['aframe_curvedimage_transparent'] = [
      '#title' => t('Transparent'),
      '#type'  => 'checkbox',
      '#value' => $this->getSetting('aframe_curvedimage_transparent'),
    ];

    // Get A-Frame global formatter settings form.
    $element += $this->globalSettingsForm($form, $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = t('Original image');
    }

    $summary[] = t('Opacity: @opacity', ['@opacity' => $this->getSetting('aframe_curvedimage_opacity')]);
    $summary[] = t('Radius: @radius', ['@radius' => $this->getSetting('aframe_curvedimage_radius')]);
    $summary[] = t('Segments radial: @segments-radial', ['@segments-radial' => $this->getSetting('aframe_curvedimage_segments_radial')]);
    $summary[] = t('Theta length: @theta-length', ['@theta-length' => $this->getSetting('aframe_curvedimage_theta_length')]);
    $summary[] = t('Theta start: @theta-start', ['@theta-start' => $this->getSetting('aframe_curvedimage_theta_start')]);
    $summary[] = t('Transparent: @transparent', ['@transparent' => $this->getSetting('aframe_curvedimage_transparent')]);

    // Get A-Frame global formatter settings summary.
    $summary = array_merge($summary, $this->globalSettingsSummary());

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

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if (!empty($image_style_setting)) {
      /** @var \Drupal\image\Entity\ImageStyle $image_style */
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }

    /** @var \Drupal\file\Entity\File $file */
    foreach ($files as $delta => $file) {
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      $item = $file->_referringItem;
      $dimensions = [
        'height' => $item->get('height')->getCastedValue(),
        'width'  => $item->get('width')->getCastedValue(),
      ];

      $url = file_create_url($file->getFileUri());
      if (isset($image_style)) {
        $image_style->transformDimensions($dimensions, $file->getFileUri());
        $url = $image_style->buildUrl($file->getFileUri());
      }

      $elements[$delta] = [
        '#type'       => 'aframe_curvedimage',
        '#attributes' => [
          'height'          => $dimensions['height'] * ($this->getSetting('aframe_curvedimage_height_ratio') / 100),
          'opacity'         => $this->getSetting('aframe_curvedimage_opacity'),
          'radius'          => $this->getSetting('aframe_curvedimage_radius'),
          'segments-radial' => $this->getSetting('aframe_curvedimage_segments_radial'),
          'src'             => $url,
          'theta-length'    => $this->getSetting('aframe_curvedimage_theta_length'),
          'theta-start'     => $this->getSetting('aframe_curvedimage_theta_start'),
          'transparent'     => $this->getSetting('aframe_curvedimage_transparent'),
        ],
        '#cache'      => [
          'tags' => $cache_tags,
        ],
      ];

      // Get A-Frame global formatter attributes.
      $elements[$delta]['#attributes'] += $this->getAttributes();
    }

    return $elements;
  }

}
