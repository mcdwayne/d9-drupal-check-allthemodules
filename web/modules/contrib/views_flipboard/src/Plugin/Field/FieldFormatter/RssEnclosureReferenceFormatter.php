<?php

namespace Drupal\views_flipboard\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'rss enclosure reference' formatter.
 *
 * @FieldFormatter(
 *   id = "rss_enclosure_reference",
 *   label = @Translation("RSS Enclosure for Reference "),
 *   description = @Translation("Render referenced media entity as RSS enclosure"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class RssEnclosureReferenceFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

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
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The entity storage for the image.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LinkGeneratorInterface $link_generator, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
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
      'image_link' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $element['image_style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => [
        '#markup' => $this->linkGenerator->generate($this->t('Configure Image Styles'), new Url('entity.image_style.collection')),
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
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = t('Original image');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity_references = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($entity_references)) {
      return $elements;
    }
    $url = NULL;
    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    foreach ($entity_references as $delta => $entity_reference) {
      $cache_contexts = [];
      if (isset($link_file)) {
        $image_uri = $entity_reference->getTarget()->getFileUri();
        // @todo Wrap in file_url_transform_relative(). This is currently
        // impossible. As a work-around, we currently add the 'url.site' cache
        // context to ensure different file URLs are generated for different
        // sites in a multisite setup, including HTTP and HTTPS versions of the
        // same site. Fix in https://www.drupal.org/node/2646744.
        $url = Url::fromUri(file_create_url($image_uri));
        $cache_contexts[] = 'url.site';
      }
      $cache_tags = Cache::mergeTags($base_cache_tags, $entity_reference->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $entity_reference->_referringItem;
      $item_attributes = $entity_reference->_attributes;
      unset($item->_attributes);
      $elements[$delta] = [
        '#theme' => 'rss_enclosure_reference_formatter',
        '#item' => $entity_reference,
        '#item_attributes' => $item_attributes,
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
