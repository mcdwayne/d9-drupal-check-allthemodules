<?php

namespace Drupal\gathercontent;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\gathercontent\Entity\MappingInterface;
use Drupal\migrate_plus\Entity\Migration;

/**
 * Create dynamic migration definitions.
 */
class MigrationDefinitionCreator {

  const BASIC_SCHEMA_GC_DESTINATION_CONFIG = [
    'langcode' => '',
    'status' => TRUE,
    'id' => '',
    'label' => '',
    'source' => [
      'plugin' => 'gathercontent_migration',
      'projectId' => '',
      'templateId' => '',
      'templateName' => '',
      'tabIds' => [],
      'fields' => [],
      'metatagFields' => [],
    ],
    'process' => [],
    'destination' => [
      'plugin' => '',
    ],
    'migration_dependencies' => [],
    'migration_tags' => [],
  ];

  /**
   * Mapping object.
   *
   * @var \Drupal\gathercontent\Entity\MappingInterface
   */
  protected $mapping;

  /**
   * Mapping data array.
   *
   * @var array
   */
  protected $mappingData;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Default language.
   *
   * @var string
   */
  protected $siteDefaultLangCode;

  /**
   * List of the generated migration definition ids.
   *
   * @var array
   */
  protected $migrationDefinitionIds = [];

  /**
   * List of the collected reference fields.
   *
   * @var array
   */
  protected $collectedReferences = [];

  /**
   * MetatagQuery helper object.
   *
   * @var \Drupal\gathercontent\MetatagQuery
   */
  protected $metatagQuery;

  /**
   * MigrationDefinitionCreator constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;

    $this->siteDefaultLangCode = $this
      ->configFactory
      ->getEditable('system.site')
      ->get('langcode');

    /** @var \Drupal\gathercontent\MetatagQuery $metatagQuery */
    $this->metatagQuery = \Drupal::service('gathercontent.metatag');
  }

  /**
   * Set the mapping object.
   */
  public function setMapping(MappingInterface $mapping) {
    $this->mapping = $mapping;
    return $this;
  }

  /**
   * Set mapping data array.
   */
  public function setMappingData(array $mappingData) {
    $this->mappingData = $mappingData;
    return $this;
  }

  /**
   * Determine if the configuration is new.
   */
  protected function isNewConfiguration($definitionId): bool {
    $configuration = $this->configFactory->get($definitionId);

    return $configuration ? FALSE : TRUE;
  }

  /**
   * Return the concatenated definitions for the given template.
   */
  protected function getGroupedDefinitions() {
    $groupedData = [];

    foreach ($this->mappingData as $tabId => $data) {
      $language = $this->siteDefaultLangCode;

      if (isset($data['language'])) {
        $language = $data['language'];
      }

      if (
        !empty($data['type'])
        && $data['type'] === 'metatag'
        && \Drupal::moduleHandler()->moduleExists('metatag')
        && $this->metatagQuery->checkMetatag(
          $this->mapping->getMappedEntityType(),
          $this->mapping->getContentType()
        )
      ) {
        $data['metatag_elements'] = $data['elements'];
        $data['elements'] = [];
      }

      if (empty($groupedData[$language]['data'])) {
        $groupedData[$language]['data'] = [];
      }

      $groupedData[$language]['tabIds'][] = $tabId;
      $groupedData[$language]['data'] = $this->arrayMergeRecursiveDistinct($groupedData[$language]['data'], $data);
    }

    $definitions = $this->getDefinitions($groupedData);

    return $definitions;
  }

  /**
   * Return the concatenated definitions created from the grouped data.
   */
  protected function getDefinitions(array $groupedData) {
    $definitions = [];

    foreach ($groupedData as $language => $data) {
      $definition = $this->buildMigrationDefinition([
        'projectId' => $this->mapping->getGathercontentProjectId(),
        'templateId' => $this->mapping->getGathercontentTemplateId(),
        'entityType' => $this->mapping->getMappedEntityType(),
        'contentType' => $this->mapping->getContentType(),
        'tabIds' => $this->formatTabIds($data['tabIds']),
      ], $data['tabIds'], $data['data'], 'gc_entity');

      $definitions[$definition['id']] = $definition;
    }

    return $definitions;
  }

  /**
   * Create migration definitions.
   */
  public function createMigrationDefinition() {
    $definitions = $this->getGroupedDefinitions();

    if (!$definitions) {
      return;
    }

    $this->setLanguageDefinitions($definitions);
    $this->setReferenceDependencies($definitions);

    foreach ($definitions as $definition) {
      $migration = Migration::create($definition);
      $migration->save();

      $this->migrationDefinitionIds[] = $definition['id'];
    }

    $this->mapping->set('migration_definitions', $this->migrationDefinitionIds);
    $this->mapping->save();
  }

  /**
   * Set reference migration dependencies and processes.
   */
  public function setReferenceDependencies(array &$definitions) {
    if (empty($this->collectedReferences)) {
      return;
    }

    foreach ($definitions as $definitionId => $definition) {
      if (!isset($this->collectedReferences[$definitionId])) {
        continue;
      }

      $references = $this->collectedReferences[$definitionId];

      foreach ($references as $element => $target) {
        $subDependencies = [];

        foreach ($target as $reference) {
          $subDefinition = $this->buildMigrationDefinition(
            [
              'projectId' => $reference['base_data']['projectId'],
              'templateId' => $reference['base_data']['templateId'],
              'entityType' => $reference['base_data']['entityType'],
              'contentType' => $reference['base_data']['contentType'],
              'tabIds' => $this->formatTabIds($reference['base_data']['tabIds']),
            ],
            $reference['base_data']['tabId'],
            $reference['data'],
            'entity_reference_revisions'
          );

          $subDependencies[$subDefinition['id']] = $subDefinition;
        }
        $this->setReferenceDependencies($subDependencies);

        $collected = [];

        foreach ($subDependencies as $subDefinitionId => $subDefinition) {
          $migration = Migration::create($subDefinition);
          $migration->save();

          $this->migrationDefinitionIds[] = $subDefinitionId;

          $definitions[$definitionId]['migration_dependencies']['optional'][] = $subDefinitionId;
          $definitions[$definitionId]['process']['collect_' . $subDefinitionId] = [
            'plugin' => 'migration_lookup',
            'migration' => $subDefinitionId,
            'source' => 'id',
          ];

          $collected[] = '@collect_' . $subDefinitionId;
        }

        if (!empty($collected)) {
          $definitions[$definitionId]['process']['get_collected_' . $element] = [
            'plugin' => 'get',
            'source' => $collected,
          ];

          $definitions[$definitionId]['process'][$element] = [
            [
              'plugin' => 'gather_content_reference_revision',
              'source' => '@get_collected_' . $element,
            ],
            [
              'plugin' => 'sub_process',
              'process' => [
                'target_id' => [
                  'plugin' => 'extract',
                  'source' => 'id',
                  'index' => [0],
                ],
                'target_revision_id' => [
                  'plugin' => 'extract',
                  'source' => 'id',
                  'index' => [1],
                ],
              ],
            ],
          ];
        }
      }
    }
  }

  /**
   * Builds the migration definition.
   */
  public function buildMigrationDefinition(array $baseData, array $tabIds, array $data, string $plugin) {
    $entityDefinition = $this->entityTypeManager->getDefinition($baseData['entityType']);
    $baseDataLabel = [
      $this->mapping->getGathercontentProject(),
      $this->mapping->getGathercontentTemplate(),
      $this->mapping->getMappedEntityType(),
    ];

    $language = $this->siteDefaultLangCode;

    if (isset($data['language'])) {
      $language = $data['language'];
    }

    $definition = self::BASIC_SCHEMA_GC_DESTINATION_CONFIG;
    $definition['langcode'] = $language;
    $definition['id'] = implode('_', $baseData);
    $definition['label'] = implode('_', $baseDataLabel) . "_$language";

    $definition['source']['projectId'] = $this->mapping->getGathercontentProjectId();
    $definition['source']['templateId'] = $this->mapping->getGathercontentTemplateId();
    $definition['source']['templateName'] = $this->mapping->getGathercontentTemplate();
    $definition['source']['tabIds'] = $tabIds;
    $definition['source']['metatagFields'] = $data['metatag_elements'] ?? [];

    $bundleKey = $entityDefinition->getKey('bundle');

    if (!empty($bundleKey)) {
      $definition['process'][$bundleKey] = [
        'plugin' => 'default_value',
        'default_value' => $baseData['contentType'],
      ];
    }

    $definition['destination']['plugin'] = $plugin . ':' . $baseData['entityType'];

    $this->setDefinitionFieldProperties($definition, $data, $tabIds, $entityDefinition);

    if (!$this->isNewConfiguration($definition['id'])) {
      $config = $this->configFactory->getEditable('migrate_plus.migration.' . $definition['id']);
      $config->delete();
    }

    return $definition;
  }

  /**
   * Set the field process and destination properties.
   */
  protected function setDefinitionFieldProperties(array &$definition, array $data, array $tabIds, EntityTypeInterface $entityDefinition) {
    $labelSet = FALSE;

    foreach ($data['elements'] as $elementId => $element) {
      if (!$element) {
        continue;
      }

      $elementKeys = explode('||', $element, 2);

      $targetFieldInfo = NULL;
      $fieldInfo = FieldConfig::load($elementKeys[0]);
      $fieldType = 'string';
      $isTranslatable = TRUE;

      if (!empty($fieldInfo)) {
        $fieldType = $fieldInfo->getType();
        $isTranslatable = $fieldInfo->isTranslatable();
      }

      $element = $this->getElementLastPart($elementKeys[0]);

      if (
        $element == $entityDefinition->getKey('label') ||
        $entityDefinition->getKey('label') === FALSE
      ) {
        $labelSet = TRUE;
      }

      if (!empty($elementKeys[1])) {
        $data['elements'][$elementId] = $elementKeys[1];
        $subElementKeys = explode('||', $elementKeys[1]);

        $targetFieldInfo = FieldConfig::load($subElementKeys[0]);
      }

      $definition['source']['fields'][] = $elementId;
      $this->setFieldDefinition($definition, $data, $tabIds, $elementId, $element, $fieldInfo, $fieldType, $targetFieldInfo, $isTranslatable);
    }

    if (!$labelSet) {
      $definition['source']['fields'][] = 'item_title';
      $definition['process'][$entityDefinition->getKey('label')] = 'item_title';
    }

    if (
      !empty($data['metatag_elements'])
      && \Drupal::moduleHandler()->moduleExists('metatag')
      && $this->metatagQuery->checkMetatag(
        $this->mapping->getMappedEntityType(),
        $this->mapping->getContentType()
      )
    ) {
      $metatagField = $this->metatagQuery->getFirstMetatagField(
        $this->mapping->getMappedEntityType(),
        $this->mapping->getContentType()
      );
      $definition['source']['fields'][] = 'meta_tags';
      $definition['process'][$metatagField] = 'meta_tags';
    }
  }

  /**
   * Set field definition.
   */
  private function setFieldDefinition(array &$definition, array $data, array $tabIds, string $elementId, string $element, EntityInterface $fieldInfo = NULL, string $fieldType, EntityInterface $targetFieldInfo = NULL, bool $isTranslatable) {
    switch ($fieldType) {
      case 'image':
      case 'file':
        $fileDir = $fieldInfo->getSetting('file_directory');
        $uriScheme = $fieldInfo->getFieldStorageDefinition()->getSetting('uri_scheme') . '://';

        $definition['process'][$element] = [
          'plugin' => 'gather_content_file',
          'source' => $elementId,
          'uri_scheme' => $uriScheme,
          'file_dir' => $fileDir,
          'language' => $this->siteDefaultLangCode,
        ];

        if ($definition['langcode'] == 'und') {
          $definition['process'][$element]['language'] = 'und';
        }

        break;

      case 'timestamp':
        $definition['process'][$element] = [
          'plugin' => 'callback',
          'callable' => 'strtotime',
        ];
        break;

      case 'date':
      case 'datetime':
        $definition['process'][$element][] = [
          'plugin' => 'callback',
          'callable' => 'strtotime',
        ];
        $definition['process'][$element][] = [
          'plugin' => 'format_date',
          'from_format' => 'U',
          'to_format' => DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
        ];
        break;

      case 'text':
      case 'text_with_summary':
        $this->setTextFieldDefinition($definition, $element, $elementId);
        $this->setTextFormat($definition, $data, $elementId, $element);
        break;

      case 'entity_reference':
        if (!empty($fieldInfo->getSetting('handler_settings')['auto_create_bundle'])) {
          $bundle = $fieldInfo->getSetting('handler_settings')['auto_create_bundle'];
        }
        else {
          $handler_settings = $fieldInfo->getSetting('handler_settings');
          $handler_settings = reset($handler_settings);
          $bundle = array_shift($handler_settings);
        }

        $definition['process'][$element] = [
          'plugin' => 'sub_process',
          'source' => $elementId,
          'process' => [
            'target_id' => [
              'plugin' => 'gather_content_taxonomy',
              'bundle' => $bundle,
              'source' => 'gc_id',
            ],
          ],
        ];
        break;

      case 'entity_reference_revisions':
        $targetEntityBundle = $targetFieldInfo->getTargetBundle();
        $language = $this->siteDefaultLangCode;

        if (isset($data['language'])) {
          $language = $data['language'];
        }

        $this->collectedReferences[$definition['id']][$element][$targetEntityBundle]['data']['language'] = $language;
        $this->collectedReferences[$definition['id']][$element][$targetEntityBundle]['data']['elements'][$elementId] = $data['elements'][$elementId];
        $this->collectedReferences[$definition['id']][$element][$targetEntityBundle]['base_data'] = [
          'projectId' => $this->mapping->getGathercontentProjectId(),
          'templateId' => $this->mapping->getGathercontentTemplateId(),
          'entityType' => $targetFieldInfo->getTargetEntityTypeId(),
          'contentType' => $targetEntityBundle,
          'tabIds' => $tabIds,
        ];
        $this->collectedReferences[$definition['id']][$element][$targetEntityBundle]['reference_data'][] = $elementId;
        break;

      default:
        $this->setTextFieldDefinition($definition, $element, $elementId);
        break;
    }

    if (
      isset($definition['process'][$element])
      && $definition['langcode'] != $this->siteDefaultLangCode
      && $definition['langcode'] != 'und'
      && $isTranslatable
    ) {
      $definition['process'][$element]['language'] = $definition['langcode'];
    }
  }

  /**
   * Set the text format for the text type fields.
   */
  protected function setTextFormat(array &$definition, array $data, string $elementId, string $element) {
    if (isset($data['element_text_formats'][$elementId])
      && !empty($data['element_text_formats'][$elementId])
      && isset($definition['process'][$element])
    ) {
      $origElement = $definition['process'][$element];
      unset($definition['process'][$element]);

      $definition['process'][$element . '/format'] = [
        'plugin' => 'default_value',
        'default_value' => $data['element_text_formats'][$elementId],
      ];
      $definition['process'][$element . '/value'] = $origElement;

      if (
        $definition['langcode'] != $this->siteDefaultLangCode &&
        $definition['langcode'] != 'und'
      ) {
        $definition['process'][$element . '/value']['language'] = $definition['langcode'];
      }
    }
  }

  /**
   * Set the text definition. Set concatenation if needed.
   */
  protected function setTextFieldDefinition(array &$definition, string $element, string $elementId) {
    // Check if the field has a /value element to support different formats.
    $key = $element . '/value';
    if (!isset($definition['process'][$key])) {
      $key = $element;
    }

    if (isset($definition['process'][$key])) {
      $definition['process'][$key]['plugin'] = 'concat';

      if (!is_array($definition['process'][$key]['source'])) {
        $definition['process'][$key]['source'] = [
          $definition['process'][$key]['source'],
        ];
      }

      $definition['process'][$key]['source'][] = $elementId;
      $definition['process'][$key]['delimiter'] = ' ';

      return;
    }

    $definition['process'][$element] = [
      'plugin' => 'get',
      'source' => $elementId,
    ];
  }

  /**
   * Return the entity field's name.
   */
  protected function getElementLastPart(string $element) {
    if (strpos($element, '.')) {
      $parts = explode('.', $element);
      return end($parts);
    }
    return $element;
  }

  /**
   * Set migration language dependencies.
   */
  protected function setLanguageDefinitions(&$definitions) {
    $defaultLangMigrationId = $this->getDefaultMigrationId($definitions);

    foreach ($definitions as $definitionId => $definition) {
      if (
        $definition['langcode'] != $this->siteDefaultLangCode &&
        $definition['langcode'] != 'und'
      ) {
        $plugin = explode(':', $definition['destination']['plugin']);
        $entityDefinition = $this->entityTypeManager->getDefinition($plugin[1]);

        $definitions[$definitionId]['destination']['translations'] = TRUE;

        $definitions[$definitionId]['process'][$entityDefinition->getKey('id')] = [
          'plugin' => 'migration_lookup',
          'source' => 'id',
          'migration' => $defaultLangMigrationId,
        ];
        $definitions[$definitionId]['process']['langcode'] = [
          'plugin' => 'default_value',
          'default_value' => $definition['langcode'],
        ];

        $definitions[$definitionId]['migration_dependencies']['optional'][] = $defaultLangMigrationId;
      }
    }
  }

  /**
   * Returns the main/default migration id for sub migrations.
   */
  protected function getDefaultMigrationId($definitions) {
    $defaultLangMigrationId = '';

    foreach ($definitions as $definition) {
      if ($definition['langcode'] == $this->siteDefaultLangCode) {
        $defaultLangMigrationId = $definition['id'];
      }
    }

    return $defaultLangMigrationId;
  }

  /**
   * Returns formatted tab ID. Removing the dashes.
   */
  protected function formatTabIds(array $tabIds) {
    $formattedTabIds = [];

    foreach ($tabIds as $tabId) {
      $formattedTabIds[] = \Drupal::database()->escapeTable($tabId);
    }

    return implode('_', $formattedTabIds);
  }

  /**
   * Merge arrays recursively and override existing values.
   */
  protected function arrayMergeRecursiveDistinct(array &$array1, array &$array2) {
    $merged = $array1;

    foreach ($array2 as $key => &$value) {
      if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
        $merged[$key] = $this->arrayMergeRecursiveDistinct($merged[$key], $value);
      }
      else {
        $merged[$key] = $value;
      }
    }

    return $merged;
  }

}
