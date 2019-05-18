<?php

/**
 * @file
 * Contains \Drupal\collect\Plugin\Field\FieldFormatter\CollectSchemaUriFormatter.
 */

namespace Drupal\collect\Plugin\Field\FieldFormatter;

use Drupal\collect\Model\ModelManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\UriLinkFormatter;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of the 'uri' formatter for Collect Schema URIs.
 *
 * @FieldFormatter(
 *   id = "collect_schema_uri",
 *   label = @Translation("Collect Schema URI Formatter"),
 *   field_types = {
 *     "uri",
 *   }
 * )
 */
class CollectSchemaUriFormatter extends UriLinkFormatter implements ContainerFactoryPluginInterface {

  /**
   * The injected model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * Constructs a new CollectSchemaUriFormatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ModelManagerInterface $model_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->modelManager = $model_manager;
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
      $container->get('plugin.manager.collect.model')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $schema_uri = $item->value;
      $elements[$delta]['schema_uri']['#markup'] = $schema_uri;
      // Display the link to the model if model plugin is applied.
      $model = $this->modelManager->loadModelByUri($schema_uri);
      if ($model) {
        $elements[$delta]['model'] = [
          '#type' => 'item',
          '#title' => $this->t('Model'),
          '#markup' => '<a href="' . $model->url() . '">' . $model->label() . '</a>',
        ];
      }
    }

    return $elements;
  }

}
