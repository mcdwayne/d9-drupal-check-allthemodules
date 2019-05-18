<?php

namespace Drupal\image_formatter_link_to_image_style\Plugin\Field\FieldFormatter;

use Drupal\image\Entity\ImageStyle;
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
 * Plugin implementation of the 'image_formatter_link_to_image_style' formatter.
 *
 * @FieldFormatter(
 *   id = "image_formatter_link_to_image_style",
 *   label = @Translation("Image link to image style"),
 *   field_types = {
 *     "image"
 *   },
 *   settings = {
 *     "image_style" = "",
 *     "image_link_style" = "",
 *     "link_class" = "",
 *     "link_rel" = "",
 *     "image_class" = ""
 *   }
 * )
 */
class ImageFormatterLinkToImageStyleFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {
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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The entity storage for the image.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, LinkGeneratorInterface $link_generator, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->linkGenerator = $link_generator;
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
      $container->get('entity.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
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
    $element['image_link_style'] = [
      '#title' => $this->t('Link to image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => [
        '#markup' => $this->linkGenerator->generate($this->t('Configure Image Styles'), new Url('entity.image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];
    $element['image_link_class'] = [
      '#title' => $this->t('Class(es) to add to the link'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('image_link_class'),
      '#description' => $this->t('Separate multiple classes by spaces.'),
    ];
    $element['image_link_rel'] = [
      '#title' => $this->t('Rel(s) attribute value to add to the link'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('image_link_rel'),
      '#description' => $this->t('Separate multiple rels by spaces.'),
    ];
    $element['image_link_image_class'] = [
      '#title' => $this->t('Class(es) to add to the image element'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('image_link_image_class'),
      '#description' => $this->t('Separate multiple classes by spaces.'),
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

    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if (!empty($image_styles[$this->getSetting('image_style')])) {
      $summary[] = $this->t('Image style: @style', ['@style' => $image_styles[$this->getSetting('image_style')]]);
    }
    else {
      $summary[] = $this->t('Image style: @style', ['@style' => t('Original image')]);
    }
    if (!empty($image_styles[$this->getSetting('image_link_style')])) {
      $summary[] = $this->t('Link to image style: @style', ['@style' => $image_styles[$this->getSetting('image_link_style')]]);
    }
    else {
      $summary[] = $this->t('Link to image style: @style', ['@style' => t('Original image')]);
    }
    if (!empty($this->getSetting('image_link_class'))) {
      $summary[] = $this->t('Link class(es): @classes', ['@classes' => $this->getSetting('image_link_class')]);
    }
    if (!empty($this->getSetting('image_link_rel'))) {
      $summary[] = $this->t('Link rel(s): @rels', ['@rels' => $this->getSetting('image_link_rel')]);
    }
    if (!empty($this->getSetting('image_link_image_class'))) {
      $summary[] = $this->t('Image class(es): @classes', ['@classes' => $this->getSetting('image_link_image_class')]);
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

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
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
        '#theme' => 'image_formatter_link_to_image_style_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#url' => $url,
        '#url_attributes' => $url_attributes,
        '#image_style' => $image_style_setting,
        '#cache' => [
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ],
      ];
    }

    return $elements;
  }

}
