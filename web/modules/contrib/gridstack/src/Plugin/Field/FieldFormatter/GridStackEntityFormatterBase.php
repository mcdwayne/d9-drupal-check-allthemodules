<?php

namespace Drupal\gridstack\Plugin\Field\FieldFormatter;

use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\blazy\Dejavu\BlazyEntityBase;
use Drupal\gridstack\GridStackFormatterInterface;
use Drupal\gridstack\GridStackManagerInterface;
use Drupal\gridstack\GridStackDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for gridstack entity reference formatters without field details.
 */
abstract class GridStackEntityFormatterBase extends BlazyEntityBase implements ContainerFactoryPluginInterface {

  use GridStackFormatterTrait;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a GridStackMediaFormatter instance.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, ImageFactory $image_factory, GridStackFormatterInterface $formatter, GridStackManagerInterface $manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->loggerFactory = $logger_factory;
    $this->imageFactory = $image_factory;
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
      $container->get('gridstack.formatter'),
      $container->get('gridstack.manager')
    );
  }

  /**
   * Overrides the blazy manager.
   */
  public function blazyManager() {
    return $this->formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = GridStackDefault::baseSettings();
    $settings['view_mode'] = '';

    return $settings;
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
    $this->formatter->preBuildElements($build, $items, $entities);

    // Build the elements.
    $this->buildElements($build, $entities, $langcode);

    // Modifies settings post building elements.
    $this->formatter->postBuildElements($build, $items, $entities);

    return $this->manager()->build($build);
  }

  /**
   * Builds the settings.
   */
  public function buildSettings() {
    $settings = $this->getSettings();
    $settings['vanilla'] = TRUE;
    $settings['plugin_id'] = $this->getPluginId();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    return [
      'breakpoints' => FALSE,
    ] + parent::getScopedFormElements();
  }

}
