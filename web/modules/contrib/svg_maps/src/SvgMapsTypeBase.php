<?php

namespace Drupal\svg_maps;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\svg_maps\Entity\SvgMapsEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Svg maps plugin plugins.
 */
abstract class SvgMapsTypeBase extends PluginBase implements SvgMapsTypeInterface, ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * Plugin label.
   *
   * @var string
   */
  protected $label;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Get label of the plugin
   *
   * @return string
   *
   */
  public function label() {
    $definition = $this->getPluginDefinition();
    // Cast the admin label to a string since it is an object.
    // @see \Drupal\Core\StringTranslation\TranslatableMarkup
    return (string) $definition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $svg_maps_path = $form_state->get('maps_path');
    foreach ($svg_maps_path as $key => $currentPath) {
      $form[$key] = $this->buildItemConfigurationForm($currentPath);
    }

    $form[] = $this->buildItemConfigurationForm();

    return $form;
  }

  protected function buildItemConfigurationForm($currentPath = NULL) {
    $path = [
      '#type' => 'textarea',
      '#title' => $this->t('Path'),
      '#description' => $this->t("Svg path."),
    ];

    $detailed_path = [
      '#type' => 'textarea',
      '#title' => $this->t('Detailed path'),
      '#description' => $this->t("Svg detailed path."),
    ];

    if($currentPath) {
      $path['#default_value'] = $currentPath['path'] ?? '';
      $detailed_path['#default_value'] = $currentPath['detailed_path'] ?? '';
    }

    return [
      '#type' => 'fieldset',
      '#title' => $this->t('Element'),
      '#tree' => TRUE,
      'path' => $path,
      'detailed_path' => $detailed_path,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configEntities = \Drupal::configFactory()->listAll('svg_maps.svg_maps_entity.'.$this->getPluginId());
    $configEntities = array_map(function ($item){
      return str_replace('svg_maps.svg_maps_entity.', '', $item);
    }, $configEntities);
    return [
      'entities' => SvgMapsEntity::loadMultiple($configEntities)
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) { }

}
