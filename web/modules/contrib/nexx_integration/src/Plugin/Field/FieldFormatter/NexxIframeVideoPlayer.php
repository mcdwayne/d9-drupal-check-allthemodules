<?php

namespace Drupal\nexx_integration\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'nexx_iframe_video_player' formatter.
 *
 * @FieldFormatter(
 *   id = "nexx_iframe_video_player",
 *   module = "nexx_integration",
 *   label = @Translation("Iframe Video Player"),
 *   field_types = {
 *     "nexx_video_data"
 *   }
 * )
 */
class NexxIframeVideoPlayer extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The nexx configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new DateTimeDefaultFormatter.
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
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The nexx configuration.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ImmutableConfig $config) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->config = $config;
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
      $container->get('config.factory')->get('nexx_integration.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'iframe_nexx_player',
        '#omnia_id' => $this->config->get('omnia_id'),
        '#video_hash' => $item->hash,
        '#container_id' => 'player--' . Crypt::randomBytesBase64(8),
        '#attached' => [
          'library' => [
            'nexx_integration/base',
          ],
        ],
      ];
    }

    return $elements;
  }

}
