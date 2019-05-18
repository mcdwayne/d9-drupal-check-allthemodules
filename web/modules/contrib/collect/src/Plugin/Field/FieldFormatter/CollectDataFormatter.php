<?php

/**
 * @file
 * Contains \Drupal\collect\Plugin\Field\FieldFormatter\CollectDataFormatter.
 */

namespace Drupal\collect\Plugin\Field\FieldFormatter;

use Drupal\collect\Model\ModelManagerInterface;
use Drupal\collect\Model\SpecializedDisplayModelPluginInterface;
use Drupal\collect\Plugin\collect\Model\DefaultModelPlugin;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\collect\TypedData\TypedDataProvider;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'collect_data' formatter.
 *
 * @FieldFormatter(
 *   id = "collect_data",
 *   label = @Translation("Data"),
 *   field_types = {
 *     "collect_data",
 *   }
 * )
 */
class CollectDataFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The injected model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * The injected Typed Data provider.
   *
   * @var \Drupal\collect\TypedData\TypedDataProvider
   */
  protected $typedDataProvider;

  /**
   * Constructs a new CollectDataFormatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ModelManagerInterface $model_manager, TypedDataProvider $typed_data_provider) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->modelManager = $model_manager;
    $this->typedDataProvider = $typed_data_provider;
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
      $container->get('plugin.manager.collect.model'),
      $container->get('collect.typed_data_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $typed_data = $this->typedDataProvider->getTypedData($items->getEntity());

    foreach ($items as $delta => $item) {
      // Use a model plugin to render data.
      $model_plugin = $this->modelManager->createInstanceFromUri($item->getEntity()->getSchemaUri());
      if ($model_plugin instanceof DefaultModelPlugin) {
        $elements[$delta]['no_display'] = [
          '#type' => 'container',
          '#markup' => $this->t('There is no plugin configured to display data.'),
          '#attributes' => array(
            'class' => array('messages messages--warning'),
          ),
        ];
        return $elements;
      }
      $elements[$delta]['plugin'] = array(
        '#type' => 'item',
      );
      if ($model_plugin instanceof SpecializedDisplayModelPluginInterface) {
        // If model plugin has custom build method, use it to build the output.
        $elements[$delta]['plugin']['data'] = $model_plugin->build($typed_data);
        return $elements;
      }
      else {
        // If there is no custom build method, build a simple table of the
        // values.
        $elements[$delta]['plugin']['data'] = $this->listProperties($typed_data);
      }
    }

    return $elements;
  }

  /**
   * Create a table with the values of a data object.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $data
   *   Container data.
   *
   * @return array
   *   Renderable array of a table of the data values.
   */
  protected function listProperties(ComplexDataInterface $data) {
    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Property'),
        $this->t('Value'),
      ],
    ];

    // Add a row for each property.
    foreach ($data->getProperties(TRUE) as $name => $property) {
      if ($name == CollectDataInterface::CONTAINER_KEY) {
        continue;
      }
      $table['#rows'][$name] = [
        [
          'data' => SafeMarkup::checkPlain($property->getDataDefinition()->getLabel()),
          'header' => TRUE,
        ],
        $this->formatValue($property),
      ];
    }

    return $table;
  }

  /**
   * Formats a typed data value for rendering.
   *
   * List values are formatted as an item list, where each element is formatted
   * recursively.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $value
   *   The data object to format.
   *
   * @return array|string
   *   The formatted data, as a renderable array or simply a string.
   */
  protected function formatValue(TypedDataInterface $value) {
    // Special case: format lists and recurse into children, but only if there
    // are more than one element.
    if ($value instanceof ListInterface) {
      if (count($value) > 1) {
        $element = ['#theme' => 'item_list'];
        foreach ($value as $item) {
          $element['#items'][] = $this->formatValue($item);
        }
        return ['data' => $element];
      }
    }

    // Display the link to the captured referenced entity container if it
    // exists and the value is valid URL. Otherwise, cast to string and escape
    // HTML.
    $string_value = $value->getString();
    if (UrlHelper::isValid($string_value, TRUE)) {
      return $this->getContainerUrl($string_value);
    }
    return SafeMarkup::checkPlain($string_value);
  }

  /**
   * Returns collect container URL that matches given URI.
   */
  public function getContainerUrl($uri) {
    $is_container = \Drupal::entityQuery('collect_container')
      ->condition('origin_uri', $uri)
      ->sort('id', 'DESC')
      ->pager(1)
      ->execute();
    if ($is_container) {
      return \Drupal::l($uri, Url::fromRoute('entity.collect_container.canonical', ['collect_container' => current($is_container)]));
    }
    return SafeMarkup::checkPlain($uri);
  }

}
