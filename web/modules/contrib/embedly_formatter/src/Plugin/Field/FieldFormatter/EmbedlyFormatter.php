<?php

namespace Drupal\embedly_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\embedly\Embedly;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'embedly_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "embedly_formatter",
 *   label = @Translation("Embedly"),
 *   field_types = {
 *     "link",
 *   }
 * )
 */
class EmbedlyFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The Embedly service.
   *
   * @var \Drupal\embedly\Embedly
   */
  protected $embedly;

  /**
   * Constructs a new EmbedlyFormatter object.
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
   * @param \Drupal\embedly\Embedly $embedly
   *   The Embedly service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, Embedly $embedly) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->embedly = $embedly;
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
      $container->get('embedly')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Build an array of URLs from items.
    $urls = [];

    foreach ($items as $delta => $item) {
      $urls[$delta] = $item->uri;
    }

    // Request Embedly.
    $data = $this->embedly->oEmbed($urls);

    if ($data) {
      foreach ($data as $index => $result) {
        $elements[$index] = [
          '#theme' => 'embedly',
          '#data' => $result,
        ];
      }
    }

    return $elements;
  }

}
