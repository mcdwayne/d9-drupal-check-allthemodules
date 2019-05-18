<?php

/**
 * @file
 * Contains Drupal\imagefield_zoom\Plugin\Field\FieldFormatter\ImageZoomFieldFormatter.
 */

namespace Drupal\imagefield_zoom\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'imagezoom_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "imagezoom_field_formatter",
 *   label = @Translation("ImageZoom"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageZoomFieldFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Image entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;
  protected $imageZoomStyles;

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
   * @param object $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   Image style storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, $current_user, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
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
      $container->get('entity.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_style' => '',
      'image_zoom_style' => 'mouseover',
      'image_touchscreen_compatible' => TRUE,
      'image_magnify' => 1,
      'image_fade_duration' => 120,
    ) + parent::defaultSettings();
  }

  /**
   * Settings form.
   *
   * @param array $form
   *    Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *    Form state array.
   *
   * @return mixed
   *    Returns mixed data.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );

    $element['image_style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];

    $image_zoom_styles = array(
      'mouseover' => t('Mouse Over'),
      'grab' => t('Grab'),
      'click' => t('Click'),
      'toggle' => t('Toggle'),
    );
    $element['image_zoom_style'] = array(
      '#title' => t('Image Zoom Style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_zoom_style'),
      '#options' => $image_zoom_styles,
    );
    $element['image_touchscreen_compatible'] = array(
      '#title' => t('Touchscreen Compatible'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('image_touchscreen_compatible'),
    );
    $element['image_magnify'] = array(
      '#title' => t('Image Magnify'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('image_magnify'),
    );
    $element['image_fade_duration'] = array(
      '#title' => t('Fade Duration'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('image_fade_duration'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $image_styles = image_style_options(FALSE);
    unset($image_styles['']);
    $image_style_setting = $this->getSetting('image_style');

    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = $this->t('Image style: @value', array('@value' => $image_styles[$image_style_setting]));
    }
    else {
      $summary[] = $this->t('Original image');
    }

    $summary[] = $this->t('Zoom style: @value', array('@value' => $this->getSetting('image_zoom_style')));
    $summary[] = $this->t('Touchscreen Compatible: @value', array('@value' => $this->getSetting('image_touchscreen_compatible')));
    $summary[] = $this->t('Image Magnify: @value', array('@value' => $this->getSetting('image_magnify')));
    $summary[] = $this->t('Fade Duration: @value', array('@value' => $this->getSetting('image_fade_duration')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $files = $this->getEntitiesToView($items, $langcode);
    $images = array();
    $original_urls = array();
    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }
    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = array();
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      $cache_contexts = array();
      $image_uri = $file->getFileUri();
      $cache_contexts[] = 'url.site';
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      if (!empty($item->_attributes)) {
        $item_attributes = $item->_attributes;
      }
      $image_target_id = $item->getValue()['target_id'];
      $image_uri = file_create_url($image_uri);
      $image_uri = parse_url($image_uri);
      $original_urls[$image_target_id] = $image_uri['path'];
      // Adding custom attributes to the img.
      $item_attributes['class'][] = 'original-image';
      // $item_attributes['width'] = '400';.
      $item_attributes['fid'] = $image_target_id;
      unset($item->_attributes);

      $images[$delta] = array(
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#prefix' => '<span class="image-zoom">',
        '#suffix' => '</span>',
        '#cache' => array(
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ),
      );
    }
    return array(
      '#theme' => 'imagefield_zoom',
      '#images' => render($images),
      '#attached' => [
        'drupalSettings' => [
          'imageFieldZoom' => [
            'image_zoom_style' => $this->getSetting('image_zoom_style'),
            'image_touchscreen_compatible' => $this->getSetting('image_touchscreen_compatible'),
            'image_magnify' => $this->getSetting('image_magnify'),
            'image_fade_duration' => $this->getSetting('image_fade_duration'),
            'image_urls' => $original_urls,
          ],
        ],
      ],
    );
  }

}
