<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\collect\Model\CollectJson.
 */

namespace Drupal\collect\Plugin\collect\Model;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Model\DynamicModelTypedDataInterface;
use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\collect\TypedData\TypedDataProvider;
use Drupal\collect\Plugin\collect\Model\Json;
use Drupal\Component\Serialization\Json as SerializationJson;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\DataDefinition;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Model plugin for JSON data with inline field description.
 *
 * The Collect JSON format represents a single entity (not strictly in the
 * Drupal sense) with a set of fields. The format has two main top-level keys:
 *   - The "fields" key contains type definitions of the fields that exist on
 *     the entity.
 *   - The "values" key contains a value for each field.
 *
 * Example data:
 * @code
 * {
 *   "fields": {
 *     "water": {
 *       "type": "integer",
 *       "label": "Amount of water",
 *       "description": "How much water is applied.",
 *       "properties": {
 *         "amount": {
 *           "type": "integer",
 *         },
 *         "unit": {
 *           "type": "string",
 *         }
 *       }
 *     }
 *   },
 *   "values": {
 *     "water": [
 *       {
 *         "amount": 2,
 *         "unit": "dl"
 *       }
 *     ]
 *   }
 * }
 * @endcode
 *
 * @todo Create official specification.
 *
 * @Model(
 *   id = "collectjson",
 *   label = @Translation("Collect JSON"),
 *   description = @Translation("Contains values and definitions for a set of fields."),
 *   patterns = {
 *     "https://drupal.org/project/collect_client/contact/",
 *     "http://schema.md-systems.ch/collect/0.0.1/collectjson/"
 *   }
 * )
 */
class CollectJson extends Json implements DynamicModelTypedDataInterface, ContainerFactoryPluginInterface {

  /**
   * The injected logger.
   *
   * @var \Psr\Log\LoggerInterface;
   */
  protected $logger;

  /**
   * The injected typed data provider.
   *
   * @var \Drupal\collect\TypedData\TypedDataProvider
   */
  protected $typedDataProvider;

  /**
   * The injected serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CollectJson plugin instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, TypedDataProvider $typed_data_provider, Serializer $serializer, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->typedDataProvider = $typed_data_provider;
    $this->serializer = $serializer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('logger.factory')->get('collect'),
      $container->get('collect.typed_data_provider'),
      $container->get('serializer'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function help() {

    return [
      ['#markup' => '<p>' . $this->t('Including the field definitions allows the values to be complex and yet interpretable on different systems.') . '</p>'],
      ['#markup' => '<p>' . $this->t('The definitions may be stored in separate containers, using the <em>Collect Field Definition</em> model plugin. This is practical for the case of numerous value containers sharing the same definitons. Field definition containers are linked to the value containers by schema URI: the origin URI of the field definition container must be identical to the schema URI of the value containers.') . '</p>'],
      [
        '#type' => 'table',
        '#caption' => $this->t('Example'),
        '#header' => [
          $this->t('Origin URI'),
          $this->t('Contents'),
          $this->t('Schema URI'),
          $this->t('Model plugin'),
        ],
        [
          ['#markup' => 'http://drupal.org/image/1'],
          ['#markup' => $this->t('Values')],
          ['#markup' => 'collectjson:drupal.org/image'],
          ['#markup' => $this->t('Collect JSON')],
        ],
        [
          ['#markup' => 'http://drupal.org/image/2'],
          ['#markup' => $this->t('Values')],
          ['#markup' => 'collectjson:drupal.org/image'],
          ['#markup' => $this->t('Collect JSON')],
        ],
        [
          ['#markup' => 'collectjson:drupal.org/image'],
          ['#markup' => $this->t('Field definitions (data types, labels, sub-properties...)')],
          ['#markup' => 'collectjson-definition:global/fields'],
          ['#markup' => $this->t('Collect Field Definition')],
        ],
      ],
      ['#markup' => '<p>' . $this->t('The properties available in a Collect JSON model are determined by the field definitions. When a field definition container is updated with new fields, they are added as properties on the corresponding Collect JSON model. (This is not supported for inline definitions.)') . '</p>']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function parse(CollectContainerInterface $collect_container) {
    $data = parent::parse($collect_container);

    // Extract _links resources which contains target entity URIs. Extend the
    // 'values' array with the fields which are not explicitly displayed as they
    // are moved into _links resources.
    if (isset($data['values']['_links'])) {
      foreach ($data['values']['_links'] as $field_name => $field_value) {
        if ($field_name == 'self') {
          continue;
        }
        $data['values'][$field_name] = $field_value;
        foreach ($field_value as $field_element_key => $field_element_value) {
          $data['values']['_link_' . $field_name][$field_element_key] = $field_element_value['href'];
        }
      }
    }

    // Attach definition data to parsed values data.
    $data['values']['attached_definition'] = $this->getDefinition($collect_container, $data['values']);

    return $data['values'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getStaticPropertyDefinitions() {
    $properties = parent::getStaticPropertyDefinitions();

    $properties['_default_title'] = new PropertyDefinition('_default_title', DataDefinition::create('string')
      ->setLabel('Default title')
      ->setDescription('The default title of a container provided by applied model.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveQueryPath($data, array $path) {
    $property = reset($path);
    if ($property == '_default_title') {
      return $this->getDefaultTitle($data);
    }

    return parent::resolveQueryPath($data, $path);
  }

  /**
   * Returns default title based on given data.
   *
   * @param mixed $data
   *   Parsed data.
   *
   * @return string
   *   Default title.
   */
  protected function getDefaultTitle($data) {
    // Get entity type and bundle from attached definition data.
    $definition = $data['attached_definition'];
    $default_title = $definition['entity_type'];

    // Expand default title with bundle label.
    if (isset($definition['bundle']) && !empty($definition['bundle']['name'])) {
      $default_title .= ' ' . $definition['bundle']['name'];
    }

    // @todo: Investigate about possibility to use label of a captured entity.
    // Use "name" in default title if it exists.
    if (isset($data['name'])) {
      // Get a first name if there are multiple names.
      $name = reset($data['name']);
      if (is_array($name)) {
        // Get a first property.
        $default_title .= ' ' . reset($name);
      }
    }
    // Otherwise, check if "title" exists.
    elseif (isset($data['title'])) {
      // Get a value of first title property in case there are multiple.
      $title = reset($data['title']);
      if (is_array($title)) {
        $default_title .= ' ' . reset($title);
      }
    }

    return $default_title;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTeaser(CollectDataInterface $data) {
    $output = parent::buildTeaser($data);
    // Reduce output simply by limiting the number of printed fields.
    // @todo This means unpredictable selection of which fields to include.
    $field_count = 0;
    // @todo Use $field->view() or something.
    foreach ($data as $id => $field) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $field */
      // Guess on some fields that are not interesting in a teaser.
      if (!in_array($id, ['uuid', 'langcode', '_container']) && $string = $field->getString()) {
        $output['fields'][$id] = array(
          '#type' => 'item',
          '#title' => $field->getDataDefinition()->getLabel(),
          '#markup' => $string,
        );
        if (++$field_count >= 5) {
          break;
        }
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function generatePropertyDefinitions(CollectContainerInterface $collect_container) {
    // Decode JSON. Log and return if decoding fails.
    $values_data = SerializationJson::decode($collect_container->getData());
    if (!isset($values_data) || !isset($values_data['values'])) {
      $this->logger->info('Collect JSON data is missing values.');
      return array();
    }

    $fields = $this->getDefinition($collect_container, $values_data)['fields'];
    // Wrap each field definition in a property definition object.
    foreach ($fields as $name => $data_definition) {
      // Simply use the name as query.
      $fields[$name] = new PropertyDefinition($name, $data_definition);
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function suggestConfig(CollectContainerInterface $container, array $plugin_definition) {
    $model = parent::suggestConfig($container, $plugin_definition);

    // Parse the schema URI to enhance the label.
    $uri = static::matchSchemaUri($container->getSchemaUri());
    $t_args = [
      '@label' => $plugin_definition['label'],
      '@type' => $uri['entity_type'],
      '@bundle' => $uri['bundle'],
    ];
    $model->setLabel(empty($uri['bundle']) ? t('@label @type', $t_args) : t('@label @type @bundle', $t_args));

    return $model;
  }

  /**
   * Checks whether a given schema URI matches schema URI for entities.
   *
   * @param string $schema_uri
   *   The given schema URI.
   *
   * @return array
   *   Matched parts of the URI, as an associative array with the following
   *   elements:
   *     - version: Schema version
   *     - domain: Origin domain
   *     - entity_type: Entity type of the contained entity
   *     - bundle: The entity bundle, or NULL if the entity type does not use
   *       bundles
   */
  static public function matchSchemaUri($schema_uri) {
    if (preg_match('@http://schema.md-systems.ch/collect/([^/]+)/collectjson/([^/]+)/entity/([^/]+)(/([^/]+))?@', $schema_uri, $matches) == 1) {
      return array(
        'version' => $matches[1],
        'domain' => $matches[2],
        'entity_type' => $matches[3],
        'bundle' => isset($matches[5]) ? $matches[5] : NULL,
      );
    }
    return FALSE;
  }

  /**
   * Returns definition data based on given values.
   *
   * @param \Drupal\collect\CollectContainerInterface $container
   *   The container.
   * @param array|null $values_data
   *   An array of container values or NULL if there is no data.
   *
   * @return array
   *   An empty array if finding definition data fails. Otherwise, an array
   *   structured of:
   *     - fields:
   *         The field definition data.
   *     - entity_type:
   *         The entity type of a captured entity.
   *     - (optional) bundle:
   *         The bundle information about captured entity.
   */
  protected function getDefinition(CollectContainerInterface $container, $values_data) {
    if (!$values_data) {
      return [];
    }

    $definition = [];
    // Get the normalized field definitions. If they are not present in the
    // sample, they should be in a separate container with this schema URI as
    // its origin URI.
    $fields = [];
    if (isset($values_data['fields'])) {
      foreach ($values_data['fields'] as $field_id => $definition) {
        $fields[$field_id] = $this->serializer->denormalize($definition, 'Drupal\Core\TypedData\DataDefinitionInterface');
      }
      $definition['fields'] = $fields;
      $definition['entity_type'] = $values_data['entity_type'];
      if (isset($values_data['bundle'])) {
        $definition['bundle'] = $values_data['bundle'];
      }
    }
    else {
      // Load field definitions if not present in the sample.
      $container_storage = $this->entityTypeManager->getStorage('collect_container');
      $definition_containers = $container_storage->loadByProperties(['origin_uri' => $container->getSchemaUri()]);
      if (empty($definition_containers)) {
        $this->logger->warning('No definition container found for %schema_uri.', ['%schema_uri' => $container->getSchemaUri()]);
        return [];
      }
      /** @var \Drupal\collect\Entity\Container $definition_container */
      $definition_container = current($definition_containers);

      $typed_data = $this->typedDataProvider->getTypedData($definition_container);
      $definition['fields'] = $typed_data->get('fields')->getValue();
      $definition['entity_type'] = $typed_data->get('entity_type')->getValue();
      if ($typed_data->get('bundle')) {
        $definition['bundle'] = $typed_data->get('bundle')->getValue();
      }
    }

    return $definition;
  }

}
