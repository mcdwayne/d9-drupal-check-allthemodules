<?php

namespace Drupal\gridstack\Plugin\Field\FieldFormatter;

use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\blazy\BlazyEntity;
use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatterBase;
use Drupal\gridstack\GridStackDefault;
use Drupal\gridstack\GridStackFormatterInterface;
use Drupal\gridstack\GridStackManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'GridStack Media' formatter.
 *
 * @FieldFormatter(
 *   id = "gridstack_media",
 *   label = @Translation("GridStack Media"),
 *   description = @Translation("Display the core Media as a GridStack."),
 *   field_types = {"entity_reference"},
 *   quickedit = {"editor" = "disabled"}
 * )
 */
class GridStackMediaFormatter extends BlazyMediaFormatterBase implements ContainerFactoryPluginInterface {

  use GridStackFormatterTrait;

  /**
   * Constructs a SlickMediaFormatter object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, ImageFactory $image_factory, BlazyEntity $blazy_entity, GridStackFormatterInterface $formatter, GridStackManagerInterface $manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $image_factory, $blazy_entity, $formatter);
    $this->formatter = $formatter;
    $this->manager = $manager;
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
      $container->get('logger.factory'),
      $container->get('image.factory'),
      $container->get('blazy.entity'),
      $container->get('gridstack.formatter'),
      $container->get('gridstack.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return GridStackDefault::extendedSettings() + parent::defaultSettings();
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->formatter;
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
    $settings = $this->buildSettings();
    $build = ['settings' => $settings];

    // Modifies settings before building elements.
    $entities = array_values($entities);
    $this->formatter->preBuildElements($build, $items, $entities);

    // Build the elements.
    $this->buildElements($build, $entities, $langcode);

    // Modifies settings post building elements.
    $this->formatter->postBuildElements($build, $items, $entities);

    return $this->manager()->build($build);
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$build, $entity, $langcode) {
    parent::buildElement($build, $entity, $langcode);

    $settings = &$build['settings'];

    // Overrides fallback breakpoint image_style with grid image_style.
    // This tells theme_blazy() to respect different image style per item.
    if (!empty($settings['breakpoints'])) {
      $this->manager()->buildImageStyleMultiple($settings, $settings['delta']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    return [
      'breakpoints'    => FALSE,
      'fieldable_form' => TRUE,
      'multimedia'     => TRUE,
      'namespace'      => 'gridstack',
      'settings'       => $this->getSettings(),
      'vanilla'        => TRUE,
      'no_ratio'       => TRUE,
    ] + parent::getScopedFormElements();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();

    return $storage->isMultiple() && $storage->getSetting('target_type') === 'media';
  }

}
