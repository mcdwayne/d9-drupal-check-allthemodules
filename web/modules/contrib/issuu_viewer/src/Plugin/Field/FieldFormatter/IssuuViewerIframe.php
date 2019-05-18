<?php

namespace Drupal\issuu_viewer\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Plugin implementation of the 'issuu_viewer_publication' formatter.
 *
 * @FieldFormatter(
 *   id = "issuu_viewer_iframe",
 *   module = "issuu_viewer",
 *   label = @Translation("Issuu document viewer"),
 *   field_types = {
 *     "issuu_viewer_document_id"
 *   },
 * )
 */
class IssuuViewerIframe extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The issuu viewer settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new IssuuViewerIFrame.
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
   *   Third party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->config = $config_factory->get('issuu_viewer.settings');
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
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $document_id = $item->value;

      // Prepare the variables for theming.
      $elements[$delta] = [
        '#theme' => 'issuu_viewer_iframe',
        '#document_id' => $document_id,
        '#background_color' => $this->config->get('issuu_viewer_default_background') ?: '',
        '#height' => $this->config->get('issuu_viewer_default_height') ?: '',
      ];
    }
    return $elements;
  }

}
