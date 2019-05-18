<?php

namespace Drupal\content_parser;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Class FieldLoaderService.
 */
class FieldLoaderService {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;
  /**
   * The Pareser default values.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $field_descriptions;
  /**
   * Drupal\Core\Field\FieldTypePluginManagerInterface definition.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $pluginManagerFieldFieldType;
  /**
   * Drupal\Core\Entity\EntityTypeBundleInfoInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new FieldLoaderService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, FieldTypePluginManagerInterface $plugin_manager_field_field_type, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->field_descriptions = $config_factory->get('content_parser.field_descriptions');
    $this->pluginManagerFieldFieldType = $plugin_manager_field_field_type;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function load($entity_type, $bundle) {
    if (!$entity_type || !$bundle) {
      return [];
    }

    $definitions = $this->entityFieldManager
                        ->getFieldDefinitions($entity_type, $bundle);

    $optional = [
      'remote_id' => [
        'label' => 'Remote ID',
        'fields' => [
          [
            'name' => 'remote_id',
            'type' => 'string',
          ]
        ]
      ],
    ];

    $retrun = [];

    foreach (array_merge($optional, $definitions) as $name => $field) {
      $isMulti = false;

      if (!is_array($field)) {
        $isMulti = $field->getFieldStorageDefinition()->getCardinality() == '-1';
      }
  
      $text = !is_array($field) ? $field->getDescription() :'';
      $example_create = $this->generateExampleText($field);
      $example_exist = $this->generateExampleText($field, false);

      $retrun[$name] = $this->getDefinitionItemArray(
        $this->getFieldDescription($entity_type, $name, 'text', $text),
        $this->getFieldDescription($entity_type, $name, 'description'),
        $this->getFieldDescription($entity_type, $name, 'default_value', ''),
        is_array($field) ? $field['label'] : $field->getLabel(),
        $name,
        $this->renderExampleCode($example_create, $isMulti),
        $this->renderExampleCode($example_exist, $isMulti),
        $this->getReferenceEntity($field),
        $isMulti
      );
    }

    return $retrun;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionItemArray($text = '', $description = '', $default_value = '', $label, $name, $example, $example_exist = null, $reference = null, $isMulti) {
    return [
      'title' => t('@label (@name) ', [
        '@label' => $label,
        '@name' => $name
      ]),
      'text' => $text,
      'code_title' => t('PHP код для поля @label (@name) ', [
        '@label' => $label,
        '@name' => $name
      ]),
      'description' => $description,
      'default_value' => $default_value,
      'example' => $example,
      'example_exist' => $example_exist,
      'reference' => $reference,
      'isMulti' => $isMulti
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceEntity($definition) {
    if (is_array($definition)) {
      return null;
    }

    if (!$definition) {
      return null;
    }

    if ($definition->getType() != 'entity_reference') {
      return null;
    }

    return $definition->getSetting('target_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDescription($entity_type, $field_name, $name, $default = '') {
    $fields = $this->field_descriptions->get($entity_type);

    if (!$fields && $entity_type != '_any') {
      return $this->getFieldDescription('_any', $field_name, $name, $default);
    }

    if (!isset($fields[$field_name]) || !isset($fields[$field_name][$name])) {
      return $default;
    }

    return $fields[$field_name][$name];
  }

  /**
   * {@inheritdoc}
   */
  public function generateExampleText($definition, $create = true) {
    if (is_array($definition)) {
      return $definition['fields'];
    }

    $storage = $definition->getFieldStorageDefinition();
    $columns = [];

    if (method_exists($definition, 'getColumns')) {
      $columns = $this->exampleFieldCode($definition->getColumns());
    } elseif(method_exists($this, $this->generateFuncByDefinition($definition)) && $create) {
      $columns = call_user_func_array(
        [$this, $this->generateFuncByDefinition($definition)], 
        [$definition]
      );
    }

    if (!$columns) {
      $fieldType = $this->pluginManagerFieldFieldType->getDefinition($definition->getType());
      $schema = $fieldType['class']::schema($storage);
      $columns = isset($schema['columns']) ? $schema['columns'] : [];
    }

    return $this->exampleFieldCode($columns, $storage->getCardinality() == '-1');
  }

  /**
   * {@inheritdoc}
   */
  public function exampleFieldCode($columns) {
    $array = [];

    foreach ($columns as $name => $data) {
      $array[] = [
        'name' => $name,
        'type' => $data['type'],
        'description' => isset($data['description']) ? $data['description'] : false,
        'isMulti' => isset($data['isMulti']) ? $data['isMulti'] : false,
        'fields' => isset($data['fields']) ? $data['fields'] : false,
      ];
    }

    return $array;
  }

  /**
   * {@inheritdoc}
   */
  public function exampleentity_referenceCode($field) {
    $entity_type = $field->getSetting('target_type');
    $handler = $field->getSetting('handler_settings');

    $isFieldable = $this->entityTypeManager
                        ->getDefinition($entity_type)
                        ->isSubclassOf(FieldableEntityInterface::class);

    if (!$isFieldable) {
      return null;
    }

    $array = [];

    if (!isset($handler['target_bundles'])) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      $bundles = array_keys($bundles);
    } else {
      $bundles = $handler['target_bundles'];
    }

    foreach ($bundles as $bundle) {
      $definitions = $this->entityFieldManager
                         ->getFieldDefinitions($entity_type, $bundle);

      foreach ($definitions as $name => $definition) {
        $storage_field_item = $definition->getFieldStorageDefinition();

        $array[$name] = [
          'name' => $name,
          'type' => $definition->getType(),
          'description' => $definition->getDescription(),
          'isMulti' => $storage_field_item->getCardinality() == '-1',
          'fields' => $this->generateExampleText($definition, false)
        ];
      }
    }

    return $array;
  }

  /**
   * {@inheritdoc}
   */
  public function renderExampleCode($fields, $isMulti) {
    $template['code'] = [
      '#theme' => 'example_fields',
      '#fields' => $fields,
      '#isMulti' => $isMulti,
      '#cache' => [
        'max-age' => 0
      ]
    ];

    return drupal_render($template);
  }

  /**
   * {@inheritdoc}
   */
  public function generateFuncByDefinition($definition) {
    return 'example' . $definition->getType() . 'Code';
  }
}
