<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\collect\Model\FieldDefinition.
 */

namespace Drupal\collect\Plugin\collect\Model;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Model\ModelInterface;
use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\Model\SpecializedDisplayModelPluginInterface;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\DataDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Model plugin for Field definition JSON data.
 *
 * @Model(
 *   id = "collect_field_definition",
 *   label = @Translation("Collect Field Definition"),
 *   description = @Translation("Field definitions for Collect JSON."),
 *   patterns = {
 *     "http://schema.md-systems.ch/collect/0.0.1/collectjson-definition/global/fields"
 *   },
 *   hidden = TRUE
 * )
 */
class FieldDefinition extends Json implements SpecializedDisplayModelPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The schema URI for CollectJSON field definitions.
   *
   * @var string
   */
  const URI = 'http://schema.md-systems.ch/collect/0.0.1/collectjson-definition/global/fields';

  /**
   * The injected serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Constructs a new CollectJson plugin instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Serializer $serializer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('serializer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function parse(CollectContainerInterface $collect_container) {
    $data = parent::parse($collect_container);

    // Denormalize data definitions.
    foreach ($data['fields'] as $field_name => $field_definition) {
      $data['fields'][$field_name] = $this->serializer->denormalize($field_definition, 'Drupal\Core\TypedData\DataDefinitionInterface');
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function build(CollectDataInterface $data) {
    $output = array();
    $definition = $data->getParsedData();
    foreach ($definition['fields'] as $id => $value) {
      $type = $value['type'];
      $output['fields'][$id] = array(
        '#type' => 'details',
        '#title' => $value['label'] . ' (' . $id . ')',
        '#open' => 'TRUE',
      );
      if ($description = $value['description']) {
        $output['fields'][$id]['description'] = array(
          '#type' => 'item',
          '#title' => $this->t('Description'),
          '#markup' => $description,
        );
      }
      if ($type == 'list') {
        $output['fields'][$id]['type'] = array(
          '#type' => 'item',
          '#title' => $this->t('Type'),
          '#markup' => $value['item_definition']['type'],
        );
        continue;
      }
      $storage = $value['storage'];
      $output['fields'][$id]['type'] = array(
        '#type' => 'item',
        '#title' => $this->t('Type'),
        '#markup' => $type,
      );
      if ($type == 'entity_reference') {
        $output['fields'][$id]['target_entity_type_id'] = array(
          '#type' => 'item',
          '#title' => $this->t('Target entity type id'),
          '#markup' => $storage['target_entity_type_id'],
        );
      }
      if ($value['required'] == 1) {
        $output['fields'][$id]['required'] = array(
          '#type' => 'item',
          '#title' => $this->t('Required'),
        );
      }
      if ($storage['cardinality'] > 1) {
        $output['fields'][$id]['cardinality'] = array(
          '#type' => 'item',
          '#title' => $this->t('Cardinality'),
          '#markup' => $storage['cardinality'],
        );
      }
      if ($storage['translatable'] == 0) {
        $output['fields'][$id]['translatable'] = array(
          '#type' => 'item',
          '#title' => $this->t('Translatable'),
        );
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTeaser(CollectDataInterface $data) {
    $output = parent::buildTeaser($data);
    $labels = array();
    foreach ($data->get('fields')->getValue() as $id => $field_definition) {
      $labels[$id] = $field_definition->getLabel();
    }
    $output['fields'] = array(
      '#type' => 'item',
      '#title' => $this->t('Fields'),
      '#markup' => implode(', ', $labels),
    );

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public static function getStaticPropertyDefinitions() {
    return [
      'fields' => new PropertyDefinition('fields', DataDefinition::create('any')
        ->setLabel('Fields')
        ->setDescription('List of field definitions')),
      'entity_type' => new PropertyDefinition('entity_type', DataDefinition::create('string')
        ->setLabel('Entity type')
        ->setDescription('The entity type id of the referenced entity')),
      'bundle' => new PropertyDefinition('bundle', DataDefinition::create('any')
        ->setLabel('Bundle')
        ->setDescription('Bundle data that captured entity corresponds to')),
    ];
  }

  /**
   * Returns field definition container id.
   *
   * The container is looked up based on the given FieldDefinition model.
   */
  public static function findDefinitionContainer(ModelInterface $collect_model) {
    $field_definition_container_ids = \Drupal::entityQuery('collect_container')
      ->condition('schema_uri', static::URI)
      ->condition('origin_uri', $collect_model->getUriPattern())
      ->execute();
    return current($field_definition_container_ids);
  }

  /**
   * Returns container typed data for given collect model.
   *
   * @param \Drupal\collect\Model\ModelInterface $collect_model
   *   The given collect model.
   *
   * @return \Drupal\collect\TypedData\CollectDataInterface|null
   *   The typed data.
   */
  public static function getContainerTypedData(ModelInterface $collect_model) {
    if ($field_definition_container_id = FieldDefinition::findDefinitionContainer($collect_model)) {
      /** @var \Drupal\collect\CollectContainerInterface $stored_field_definition_container */
      $stored_field_definition_container = \Drupal::entityManager()->getStorage('collect_container')->load($field_definition_container_id);
      return \Drupal::service('collect.typed_data_provider')->getTypedData($stored_field_definition_container);
    }
  }

  /**
   * Removes entity reference fields from fields definitions data.
   *
   * @param array $field_definitions
   *   An associative array containing field definitions data.
   *
   * @return array
   *   The fields definitions data array without entity reference fields.
   */
  public static function removeEntityReferenceFields(array $field_definitions) {
    foreach ($field_definitions as $field_name => $field_definition) {
      if (substr($field_name, 0, 6) === '_link_') {
        unset($field_definitions[$field_name]);
      }
    }

    return $field_definitions;
  }

}
