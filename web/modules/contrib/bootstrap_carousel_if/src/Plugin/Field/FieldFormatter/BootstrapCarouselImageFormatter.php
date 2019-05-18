<?php

namespace Drupal\bootstrap_carousel_if\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'bootstrap_image_carousel_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "bootstrap_carousel_image_formatter",
 *   label = @Translation("Bootstrap Carousel"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class BootstrapCarouselImageFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Field\FormatterBase::__construct()
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, LinkGeneratorInterface $link_generator, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->linkGenerator = $link_generator;
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Plugin\ContainerFactoryPluginInterface::create()
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
      $container->get('link_generator'),
      $container->get('entity.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'interval' => '5000',
      'pause' => 0,
      'wrap' => 0,
      'keyboard' => 0,
      'indicators' => 1,
      'controls' => 1,
      'background' => 0,
      'background_pos' => 'center center',
      'width' => '100%',
      'height' => '100px',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['interval'] = [
      '#title' => $this->t('Interval'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('interval'),
      '#size' => 10,
      '#required' => TRUE,
    ];
    $element['pause'] = [
      '#title' => $this->t('Pause on hover'),
      '#type' => 'select',
      '#options' => [0 => 'no', 'hover' => 'yes'],
      '#default_value' => $this->getSetting('pause'),
    ];
    $element['wrap'] = [
      '#title' => $this->t('Wrap'),
      '#type' => 'select',
      '#options' => [0 => 'no', 1 => 'yes'],
      '#default_value' => $this->getSetting('wrap'),
    ];
    $element['indicators'] = [
      '#title' => $this->t('Indicators'),
      '#type' => 'select',
      '#options' => [0 => 'no', 1 => 'yes'],
      '#default_value' => $this->getSetting('indicators'),
    ];
    $element['controls'] = [
      '#title' => $this->t('Controls'),
      '#type' => 'select',
      '#options' => [0 => 'no', 1 => 'yes'],
      '#default_value' => $this->getSetting('controls'),
    ];
    $image_styles = image_style_options(FALSE);
    $element['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => [
        '#markup' => $this->linkGenerator->generate($this->t('Configure Image Styles'), new Url('entity.image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];
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

    $settings = $this->getSettings();

    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $settings['image_style'];
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = $this->t('Bootstrap carousel : @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = $this->t('Bootstrap carousel : Original image');
    }

    $summary[] = $this->t('Interval @interval, @pause, @wrap, @indicators, @controls', [
      '@interval' => $settings['interval'],
      '@pause' => $settings['pause'] ? $this->t('pause on hover') : $this->t('no pause'),
      '@wrap' => $settings['wrap'] ? $this->t('with wrap') : $this->t('no wrap'),
      '@indicators' => $settings['indicators'] ? $this->t('with indicators') : $this->t('no indicators'),
      '@controls' => $settings['controls'] ? $this->t('with controls') : $this->t('no controls'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Field\FormatterInterface::viewElements()
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $slides = [];
    $element = [];

    // Build files array.
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $slides;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;

      $slides[$delta] = [
        'title' => !empty($items[$delta]) ? $items[$delta]->getValue()['title'] : '',
        'image' => [
          '#theme' => 'image_formatter',
          '#item' => $item,
          '#item_attributes' => $item_attributes,
          '#image_style' => $image_style_setting,
          '#cache' => [
            'tags' => $cache_tags,
          ],
        ],
      ];

    }

    // Build theme array.
    $element[0] = [
      '#theme' => 'bootstrap_carousel',
      '#slides' => $slides,
      '#interval' => $this->getSetting('interval'),
      '#pause' => $this->getSetting('pause'),
      '#wrap' => $this->getSetting('wrap'),
      '#indicators' => count($slides) == 1 ? '0' : $this->getSetting('indicators'),
      '#controls' => count($slides) == 1 ? '0' : $this->getSetting('controls'),
    ];

    return $element;
  }

}
