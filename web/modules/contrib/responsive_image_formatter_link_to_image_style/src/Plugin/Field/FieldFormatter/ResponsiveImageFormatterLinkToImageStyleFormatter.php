<?php

namespace Drupal\responsive_image_formatter_link_to_image_style\Plugin\Field\FieldFormatter;

use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'responsive_image_formatter_link_to_image_style' formatter.
 *
 * @FieldFormatter(
 *   id = "responsive_image_formatter_link_to_image_style",
 *   label = @Translation("Responsive Image link to image style"),
 *   field_types = {
 *     "image"
 *   },
 *   settings = {
 *     "responsive_image_style" = "",
 *     "image_link_style" = "",
 *     "link_class" = "",
 *     "link_rel" = "",
 *     "image_class" = ""
 *   }
 * )
 */
class ResponsiveImageFormatterLinkToImageStyleFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The responsive image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $responsiveImageStyleStorage;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

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
   * Constructs a ResponsiveImageFormatter object.
   * ResponsiveImageFormatterLinkToImageStyleFormatter constructor.
   *
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param string $label
   * @param string $view_mode
   * @param array $third_party_settings
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, LinkGeneratorInterface $link_generator, EntityStorageInterface $responsive_image_style_storage, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->linkGenerator = $link_generator;
    $this->responsiveImageStyleStorage = $responsive_image_style_storage;
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
      $container->get('current_user'),
      $container->get('link_generator'),
      $container->get('entity.manager')->getStorage('responsive_image_style'),
      $container->get('entity.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive_image_style' => '',
      'image_link_style' => '',
      'image_link_class' => '',
      'image_link_rel' => '',
      'image_link_image_class' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $responsive_image_options = [];
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();

    if ($responsive_image_styles && !empty($responsive_image_styles)) {
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    $element['responsive_image_style'] = [
      '#title' => t('Responsive image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('responsive_image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $responsive_image_options,
      '#description' => [
        '#markup' => $this->linkGenerator->generate($this->t('Configure Responsive Image Styles'), new Url('entity.responsive_image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer responsive image styles'),
      ],
    ];
    $element['image_link_style'] = [
      '#title' => t('Link to image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => [
        '#markup' => $this->linkGenerator->generate($this->t('Configure Image Styles'), new Url('entity.image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];
    $element['image_link_class'] = [
      '#title' => t('Class(es) to add to the link'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('image_link_class'),
      '#description' => t('Separate multiple classes by spaces.'),
    ];
    $element['image_link_rel'] = [
      '#title' => t('Rel(s) attribute value to add to the link'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('image_link_rel'),
      '#description' => t('Separate multiple rels by spaces.'),
    ];
    $element['image_link_image_class'] = [
      '#title' => t('Class(es) to add to the image element'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('image_link_image_class'),
      '#description' => t('Separate multiple classes by spaces.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    $image_styles = image_style_options(FALSE);

    // Unset possible 'No defined styles' option.
    unset($image_styles['']);

    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if ($responsive_image_style) {
      $summary[] = t('Responsive image style: @responsive_image_style', ['@responsive_image_style' => $responsive_image_style->label()]);
    }
    else {
      $summary[] = t('Responsive Image style: @style', ['@style' => t('Original image')]);
    }

    if (!empty($image_styles[$this->getSetting('image_link_style')])) {
      $summary[] = t('Link to image style: @style', ['@style' => $image_styles[$this->getSetting('image_link_style')]]);
    }
    else {
      $summary[] = t('Link to image style: @style', ['@style' => t('Original image')]);
    }

    if (!empty($this->getSetting('image_link_class'))) {
      $summary[] = t('Link class(es): @classes', ['@classes' => $this->getSetting('image_link_class')]);
    }

    if (!empty($this->getSetting('image_link_rel'))) {
      $summary[] = t('Link rel(s): @rels', ['@rels' => $this->getSetting('image_link_rel')]);
    }

    if (!empty($this->getSetting('image_link_image_class'))) {
      $summary[] = t('Image class(es): @classes', ['@classes' => $this->getSetting('image_link_image_class')]);
    }

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

    $responsive_image_style_setting = $this->getSetting('responsive_image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];

    if (!empty($responsive_image_style_setting)) {
      $responsive_image_style = $this->responsiveImageStyleStorage->load($responsive_image_style_setting);
      $image_styles_to_load = [];
      if ($responsive_image_style) {
        $base_cache_tags = Cache::mergeTags($base_cache_tags, $responsive_image_style->getCacheTags());
        $image_styles_to_load = $responsive_image_style->getImageStyleIds();
      }

      $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);
      foreach ($image_styles as $image_style) {
        $base_cache_tags = Cache::mergeTags($base_cache_tags, $image_style->getCacheTags());
      }
    }

    foreach ($files as $delta => $file) {
      $vars = [
        'uri' => $file->getFileUri(),
        'responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
      ];
      template_preprocess_responsive_image($vars);

      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());
      $cache_contexts = ['url.site'];

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      if (!empty($this->getSetting('image_link_image_class'))) {
        if (!isset($item_attributes['class'])) {
          $item_attributes['class'] = [];
        }
        elseif (!is_array($item_attributes['class'])) {
          $item_attributes['class'] = explode(' ', $item_attributes['class']);
        }
        $item_attributes['class'] = array_merge($item_attributes['class'], explode(' ', $this->getSetting('image_link_image_class')));
      }

      if (!empty($this->getSetting('image_link_style'))) {
        $image_link_style = ImageStyle::load($this->getSetting('image_link_style'));
        $image_uri = $image_link_style->buildUrl($file->getFileUri());
      }
      else {
        $image_uri = $file->getFileUri();
      }
      $url = Url::fromUri(file_create_url($image_uri));

      $url_attributes = [];
      if (!empty($this->getSetting('image_link_class'))) {
        $url_attributes['class'] = explode(' ', $this->getSetting('image_link_class'));
      }
      if (!empty($this->getSetting('image_link_rel'))) {
        $url_attributes['rel'] = explode(' ', $this->getSetting('image_link_rel'));
      }

      $elements[$delta] = [
        '#theme' => 'responsive_image_formatter_link_to_image_style_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#url' => $url,
        '#url_attributes' => $url_attributes,
        '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
        '#cache' => [
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ],
      ];
    }

    return $elements;
  }

}
