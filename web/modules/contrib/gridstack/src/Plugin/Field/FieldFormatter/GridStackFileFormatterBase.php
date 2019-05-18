<?php

namespace Drupal\gridstack\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\blazy\BlazyOEmbed;
use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatterBase;
use Drupal\gridstack\GridStackFormatterInterface;
use Drupal\gridstack\GridStackManagerInterface;
use Drupal\gridstack\GridStackDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for gridstack image and file ER formatters.
 */
abstract class GridStackFileFormatterBase extends BlazyFileFormatterBase {

  /**
   * Constructs a GridStackImageFormatter instance.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ImageFactory $image_factory, GridStackFormatterInterface $formatter, GridStackManagerInterface $manager, BlazyOEmbed $oembed) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $image_factory, $formatter);
    $this->formatter = $formatter;
    $this->manager = $manager;
    $this->blazyOembed = $oembed;

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
      $container->get('image.factory'),
      $container->get('gridstack.formatter'),
      $container->get('gridstack.manager'),
      $container->get('blazy.oembed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return GridStackDefault::imageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entities = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($entities)) {
      return [];
    }

    // Collects specific settings to this formatter.
    $build = ['settings' => $this->buildSettings()];

    // Modifies settings before building elements.
    $this->formatter->preBuildElements($build, $items, $entities);

    // Build the elements.
    $this->buildElements($build, $entities);

    // Modifies settings post building elements.
    $this->formatter->postBuildElements($build, $items, $entities);

    return $this->manager()->build($build);
  }

  /**
   * Build the gridstack carousel elements.
   */
  public function buildElements(array &$build, $files) {
    $settings = &$build['settings'];
    $item_id = $settings['item_id'];

    foreach ($files as $delta => $file) {
      $settings['delta'] = $delta;
      $settings['type'] = 'image';

      /** @var Drupal\image\Plugin\Field\FieldType\ImageItem $item */
      $item = $file->_referringItem;

      $settings['file_tags'] = $file->getCacheTags();
      $settings['uri'] = $file->getFileUri();

      // Overrides fallback breakpoint image_style with grid image_style.
      // This tells theme_blazy() to respect different image style per item.
      if (!empty($settings['breakpoints'])) {
        $this->manager()->buildImageStyleMultiple($settings, $delta);
      }

      $element = ['item' => $item, 'settings' => $settings];

      // If imported Drupal\blazy\Dejavu\BlazyVideoTrait.
      $this->buildElement($element, $file);

      // Image with responsive image, lazyLoad, and lightbox supports.
      $element[$item_id] = $this->formatter->getBlazy($element);

      if (!empty($settings['caption'])) {
        foreach ($settings['caption'] as $caption) {
          $element['caption'][$caption] = empty($element['item']->{$caption}) ? [] : ['#markup' => Xss::filterAdmin($element['item']->{$caption})];
        }
      }

      // Build individual gridstack item.
      $build['items'][$delta] = $element;

      unset($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    if (isset($element['image_style'])) {
      $element['image_style']['#description'] = $this->t('This will be treated as the fallback image style if the optionset image styles not provided.');
    }

    return $element;
  }

  /**
   * Builds the settings.
   */
  public function buildSettings() {
    $settings = parent::buildSettings();
    $settings['blazy'] = TRUE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    return [
      'background'  => TRUE,
      'breakpoints' => FALSE,
      'no_ratio'    => TRUE,
    ] + parent::getScopedFormElements();
  }

}
