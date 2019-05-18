<?php

namespace Drupal\elastic_search\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Drupal\elastic_search\Entity\FieldableEntityMapInterface;
use Drupal\elastic_search\Plugin\ElasticAbstractField\ElasticAbstractFieldInterface;
use Drupal\elastic_search\Plugin\ElasticAbstractFieldFactory;
use Drupal\elastic_search\Plugin\EntityTypeDefinitionsInterface;
use Drupal\elastic_search\Plugin\EntityTypeDefinitionsManager;
use Drupal\elastic_search\Utility\ArrayKeyToCamelCaseHelper;
use Drupal\elastic_search\Utility\TypeMapper;
use Drupal\elastic_search\ValueObject\IdDetails;
use Drupal\field\Entity\FieldConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FieldableEntityMapForm.
 *
 * @package Drupal\elastic_search\Form
 */
class FieldableEntityMapForm extends EntityForm {

  /**
   * Suffix to the field name used in the form to identify the number of mapping
   * fields that should be rendered
   */
  const FORM_COUNTER_SUFFIX = '_num_names';

  /**
   * TypeMapper gets the appropriate elasticsearch field type options for each
   * drupalfield type
   *
   * @var \Drupal\elastic_search\Utility\TypeMapper
   */
  protected $typeMapper;

  /**
   * EntityTypeDefinitions plugin manager
   *
   * @var \Drupal\elastic_search\Plugin\EntityTypeDefinitionsManager
   */
  protected $entityTypeDefinitionsManager;

  /**
   * Psr Logging Interface for errors
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var AccountProxyInterface
   */
  protected $user;

  /**
   * @var \Drupal\elastic_search\ElasticAbstractFieldFactory
   */
  protected $elasticAbstractFieldFactory;

  /**
   * FieldableEntityMapForm constructor.
   *
   * @param \Drupal\elastic_search\Utility\TypeMapper                  $typeMapper
   * @param \Drupal\Core\Config\ConfigFactoryInterface                 $configFactory
   * @param \Drupal\elastic_search\Plugin\EntityTypeDefinitionsManager $entityTypeDefinitionsManager
   * @param \Psr\Log\LoggerInterface                                   $logger
   * @param \Drupal\Core\Session\AccountProxyInterface                 $user
   * @param \Drupal\elastic_search\Plugin\ElasticAbstractFieldFactory         $abstractFieldFactory
   */
  public function __construct(TypeMapper $typeMapper,
                              ConfigFactoryInterface $configFactory,
                              EntityTypeDefinitionsManager $entityTypeDefinitionsManager,
                              LoggerInterface $logger,
                              AccountProxyInterface $user,
                              ElasticAbstractFieldFactory $abstractFieldFactory) {
    $this->typeMapper = $typeMapper;
    $this->configFactory = $configFactory;
    $this->entityTypeDefinitionsManager = $entityTypeDefinitionsManager;
    $this->logger = $logger;
    $this->user = $user;
    $this->elasticAbstractFieldFactory = $abstractFieldFactory;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('elastic_search.mapping.type_mapper'),
                      $container->get('config.factory'),
                      $container->get('plugin.manager.elastic_entity_type_definition_plugin'),
                      $container->get('logger.factory')
                                ->get('elastic.mapping.entity_form'),
                      $container->get('current_user'),
                      $container->get('elastic_search.elastic_abstract_field.factory'));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\elastic_search\Entity\FieldableEntityMapInterface $entity */
    $entity = $this->getEntity();

    $idDetails = $entity->getIdDetails();

    $this->addFormHeader($form, $form_state, $idDetails);

    //If advanced settings are enabled render them
    if ($this->user->hasPermission('administer advanced elasticsearch')) {
      $this->addAdvancedFields($form, $form_state);
    }

    //Add the actual fields and their config
    $this->addFields($form, $form_state, $idDetails);

    $this->addCompareButtons($form, $form_state, $idDetails);

    return $form;
  }

  /**
   * @param array                                        $form
   * @param \Drupal\Core\Form\FormStateInterface         $form_state
   * @param \Drupal\elastic_search\ValueObject\IdDetails $idDetails
   */
  protected function addFormHeader(array &$form,
                                   FormStateInterface $form_state,
                                   IdDetails $idDetails) {

    //Label and id get filled in automatically, but we add theme here to get them processed into the config entity
    /** @var FieldableEntityMap $fieldable_entity_map */
    $fieldable_entity_map = $this->entity;

    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#maxlength'     => 255,
      '#default_value' => $idDetails->getId(),
      '#description'   => $this->t("Label for the Fieldable entity map."),
      '#required'      => TRUE,
      '#access'        => FALSE,
    ];
    $form['id'] = [
      '#type'          => 'machine_name',
      '#default_value' => $idDetails->getId(),
      '#machine_name'  => [
        'exists' => '\Drupal\elastic_search\Entity\FieldableEntityMap::load',
      ],
      '#access'        => FALSE,
    ];

    $form['active'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Active'),
      '#description'   => $this->t('Content will be indexed'),
      '#default_value' => $fieldable_entity_map->isNew() ? TRUE :
        $fieldable_entity_map->isActive(),
    ];

    $form['child_only'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Child Only'),
      '#description'   => $this->t('If true then no document type will be created for this content, and it will only be indexed as a child document'),
      '#default_value' => $fieldable_entity_map->isChildOnly(),
    ];

    $form['simple_reference'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Simple References'),
      '#description'   => $this->t('If true then all references in this document type will only use a simple reference value (uuid)'),
      '#default_value' => $fieldable_entity_map->isSimpleReference(),
    ];

    $form['recursion_depth'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Recursion Depth'),
      '#description'   => $this->t('How deeply is this mapping allowed to recurse through child mappings to flatten them into the document? ' .
                                   "When the recursion level is reached the UUID of the linked content will be made available instead. Be aware that this has the potential to blow up storage space if too much recursion is used." .
                                   "This setting is only used when this is the parent object of the mapping. When this object is used as a child the setting will be ignored and all objects mapped from this entity will count towards the parent recursion depth"),
      '#min'           => 0,
      '#max'           => 256,
      '#default_value' => $fieldable_entity_map->getRecursionDepth(),
    ];

  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function addAdvancedFields(array &$form,
                                       FormStateInterface $form_state) {

    /** @var FieldableEntityMap $fieldable_entity_map */
    $fieldable_entity_map = $this->entity;

    $form['dynamic_mapping'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Dynamic Mapping'),
      '#description'   => $this->t('If true then all configuration below will be ignored and data will be mapped dynamically to elastic search'),
      '#default_value' => $fieldable_entity_map->hasDynamicMapping(),
      '#ajax'          => [
        'callback' => 'Drupal\elastic_search\Form\FieldableEntityMapFormCallbacks::advancedDynamicMappingCallback',
        'wrapper'  => "fieldset-wrapper",
      ],
    ];
    if (!$form_state->hasValue('dynamic_mapping')) {
      $form_state->setValue('dynamic_mapping',
                            $fieldable_entity_map->hasDynamicMapping());
    }
  }

  /**
   * @param array                                        $form
   * @param \Drupal\Core\Form\FormStateInterface         $form_state
   * @param \Drupal\elastic_search\ValueObject\IdDetails $idDetails
   *
   * @throws \Exception
   */
  protected function addFields(array &$form,
                               FormStateInterface $form_state,
                               IdDetails $idDetails) {

    $form['fields'] = [
      '#tree'        => TRUE,
      '#type'        => 'details',
      '#title'       => $this->t('Elastic field mappings'),
      '#description' => $this->t('Map fields to elastic types, will try to choose the most appropriate values for each field. Choosing none means the field will not be indexed'),
      '#collapsible' => TRUE,
      '#prefix'      => "<div id=fieldset-wrapper>",
      '#suffix'      => '</div>',
    ];

    $fields = $this->getFieldsFromFieldDefinitionPlugin($idDetails);

    /** @var FieldDefinitionInterface|BaseFieldDefinition $bundle_field */
    foreach ($fields as $id => $bundle_field) {
      $this->addBundleField($form, $form_state, ['fields', $id], $bundle_field);
    }
  }

  /**
   * @param \Drupal\elastic_search\ValueObject\IdDetails $idDetails
   *
   * @return array
   */
  private function getFieldsFromFieldDefinitionPlugin(IdDetails $idDetails): array {
    try {
      if ($this->entityTypeDefinitionsManager->hasDefinition($idDetails->getEntity())) {
        $fieldDefinitionPlugin = $this->entityTypeDefinitionsManager->createInstance($idDetails->getEntity());
      } else {
        /** @var EntityTypeDefinitionsInterface $fieldDefinitionPlugin */
        $fieldDefinitionPlugin = $this->entityTypeDefinitionsManager->createInstance('generic');
      }

      $fields = $fieldDefinitionPlugin->getFieldDefinitions($idDetails->getEntity(),
                                                            $idDetails->getBundle());

    } catch (\Throwable $t) {
      $this->logger->warning("Could not get field definitions for: {$idDetails->getEntity()} {$idDetails->getBundle()}");
      $fields = [];
    }

    return $fields;

  }

  /**
   * @param array                                        $form
   * @param \Drupal\Core\Form\FormStateInterface         $form_state
   * @param \Drupal\elastic_search\ValueObject\IdDetails $idDetails
   */
  protected function addCompareButtons(array &$form,
                                       FormStateInterface $form_state,
                                       IdDetails $idDetails) {

    $form['view_dsl'] = [
      '#type'     => 'submit',
      '#id'       => 'view_dsl',
      '#value'    => $this->t('View Map DSL'),
      '#submit'   => ['Drupal\elastic_search\Form\FieldableEntityMapFormCallbacks::viewMappingCallback'],
      '#disabled' => $this->entity->isNew(),
    ];

  }

  /**
   * @param array                                       $form
   * @param \Drupal\Core\Form\FormStateInterface        $form_state
   * @param array                                       $id
   *   An array of parent id's will be used with NestedArray
   * @param \Drupal\Core\Field\FieldDefinitionInterface $bundle_field
   *
   * @throws \Exception
   */
  protected function addBundleField(array &$form,
                                    FormStateInterface $form_state,
                                    array $id,
                                    FieldDefinitionInterface $bundle_field) {

    $config = $this->configFactory->get('elastic_search.server');

    $count = $this->getFieldMappingCount($form_state, $id);

    $idString = $this->getIdString($id);
    $details = [
      '#type'   => 'details',
      '#title'  => end($id),
      '#tree'   => TRUE,
      '#open'   => $count > 1,
      '#prefix' => "<div id=field-wrapper-{$idString}>",
      '#suffix' => '</div>',
    ];
    NestedArray::setValue($form, $id, $details);

    $mapId = $id;
    $mapId[] = 'map';
    $this->buildMappingFieldList($form,
                                 $form_state,
                                 $this->getDefaultValues($mapId),
                                 $id,
                                 $bundle_field);

    $nested = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Is nested'),
      '#description'   => $this->t('calculated automatically from cardinality, this field shows the computed value to be saved, and not the current value'),
      '#default_value' => $this->isNested($bundle_field,
                                          $id,
                                          $form),
      '#access'        => (bool) $config->get('advanced.developer.active'),
      '#disabled'      => (bool) $form_state->getValue('dynamic_mapping',
                                                       FALSE),
    ];
    $nest = $id;
    $nest[] = 'nested';
    NestedArray::setValue($form, $nest, $nested);

    $supportsFields = $this->typeMapper->supportsFields($bundle_field->getType());
    if ($supportsFields) {
      $this->addMappingButtons($form, $form_state, $id, $count);
    }
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array                                $id
   *
   * @return int
   */
  protected function getFieldMappingCount(FormStateInterface $form_state,
                                          array $id): int {

    $compositeId = $this->getIdString($id) . self::FORM_COUNTER_SUFFIX;

    if ($exists = $form_state->get($compositeId)) {
      return $exists;
    }

    // Set the mapping count to 1 if new, otherwise try to get from entity, or default to 1
    if ($this->entity->isNew()) {
      if (empty($form_state->get($compositeId))) {
        $form_state->set($compositeId, 1);
      }
    } else {
      $data = $this->entity->getFields();
      if ($id[0] === 'fields') {
        array_shift($id);
      }
      array_push($id, 'map');
      if (NestedArray::keyExists($data, $id)) {
        $form_state->set($compositeId,
                         count(NestedArray::getValue($data, $id)));
      } else {
        $form_state->set($compositeId, 1);
      }
    }

    return $form_state->get($compositeId);

  }

  /**
   * @param array $parents
   *
   * @return array|null
   */
  protected function getDefaultValues(array $parents) {
    $fields = $this->entity->get('fields') ?? [];
    if ($parents[0] === 'fields') {
      array_shift($parents);
    }
    return NestedArray::getValue($fields, $parents) ?? [];
  }

  /**
   * This function will always recalculate what it thinks the nesting value
   * should be. Even if you have set this previously when you load the mapping
   * form it will actually load the value that it thinks correct There is no
   * reason to turn off mapping for multi value fields so this enforces that
   * they are on if there is a multiple cardinality
   *
   * @param BaseFieldDefinition|FieldConfig $bundle_field
   *
   * @return bool
   */
  protected function isNested($bundle_field, $id, $form): bool {

    $states = NestedArray::getValue($form, array_merge($id, ['map']));

    $stateNested = FALSE;
    foreach ($states as $state) {
      if ($state['type']['#default_value'] === 'object') {
        $stateNested = TRUE;
      }
    }

    // Determining if the field type is a nested field.
    $isNested = $this->fieldTypeIsNested($bundle_field->getType());

    if ($bundle_field instanceof BaseFieldDefinition) {
      /** BaseFieldDefinition $bundle_field */
      $cardinality = ($bundle_field->getCardinality() !== 1) || $isNested;
    } else {
      try {
        $sd = $bundle_field->getFieldStorageDefinition();
        $cardinality = ($sd->getCardinality() !== 1) || $isNested;
      } catch (\Exception $e) {
        $this->logger->warning("could not get cardinality to determine nesting setting for {$bundle_field->getName()}");
        $cardinality = FALSE;
      }
    }

    return ($stateNested && $cardinality) || $cardinality;
  }

  /**
   * Finds a ElasticAbstractField plugin for the given field type to determine
   * if the field type is a nested field.
   *
   * @param string $fieldType
   *
   * @return bool
   */
  protected function fieldTypeIsNested(string $fieldType): bool {
    if ($plugin = $this->elasticAbstractFieldFactory->getAbstractFieldPlugin($fieldType)) {
      if ($plugin instanceof ElasticAbstractFieldInterface) {
        return $plugin->isNested();
      }
    }
    return FALSE;
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array                                $defaults
   * @param array                                $id
   * @param BaseFieldDefinition|FieldConfig      $bundle_field
   *
   * @throws \Exception
   */
  protected function buildMappingFieldList(array &$form,
                                           FormStateInterface $form_state,
                                           array $defaults,
                                           array $id,
                                           $bundle_field) {

    $type = $bundle_field->getType();
    $count = $form_state->get($this->getIdString($id) .
                              self::FORM_COUNTER_SUFFIX);

    $options = $this->typeMapper->getFieldOptions($type);

    $this->getDefaultSuggestion($type, $defaults, $options);

    $disabled = (bool) $form_state->getValue('dynamic_mapping', FALSE);
    /** @noinspection ForeachInvariantsInspection */
    for ($i = 0; $i < (int) $count; $i++) {
      $idString = $this->getIdString($id);
      $details = [
        '#type'     => 'details',
        '#title'    => end($id),
        '#tree'     => TRUE,
        '#open'     => TRUE,
        '#prefix'   => "<div id=field-wrapper-{$idString}>",
        '#suffix'   => '</div>',
        '#disabled' => $disabled,
      ];
      $selectPos = array_merge($id, ['map', $i]);
      NestedArray::setValue($form, $selectPos, $details);

      $end = end($id);
      $select = [
        '#type'          => 'select',
        '#title'         => $this->t($end),
        '#description'   => $this->t('Mapping type for field type: ' .
                                     $type),
        '#default_value' => $defaults[$i]['type'] ?? reset($options),
        '#options'       => $options,
        '#disabled'      => $disabled,
        '#ajax'          => [
          'callback' => 'Drupal\elastic_search\Form\FieldableEntityMapFormCallbacks::addObjectMappingCallback',
          'wrapper'  => "field-wrapper-{$idString}",
        ],
      ];
      $selectPos = array_merge($id, ['map', $i, 'type']);
      NestedArray::setValue($form, $selectPos, $select);

      $formAdditions = $this->typeMapper->getFormAdditions($defaults[$i]['type']
                                                           ?? reset($options),
                                                           $defaults[$i]['options']
                                                           ?? [],
                                                           $i);
      if (!empty($formAdditions)) {
        NestedArray::setValue($form,
                              array_merge($id, ['map', $i, 'options']),
                              $formAdditions);
      }

      if ($i > 0) {
        $this->addIdentifierField($form, $defaults, $id, $i);
      }

      if ($defaults[$i]['type'] === 'object') {
        $this->buildHiddenObjectFields($form, $bundle_field, $id, $i);
      }

    }
  }

  /**
   * @param array $form
   * @param array $defaults
   * @param array $id
   * @param int   $i
   */
  private function addIdentifierField(array &$form,
                                      array $defaults,
                                      array $id,
                                      int $i) {

    if ($i === 1) {
      //We only offer a suggestion for the first identifier, as they should be unique
      //and offering the field name on every field instance would go against this
      $default = (empty($defaults[$i]['identifier']) ?
        $defaults[$i]['type'] : $defaults[$i]['identifier']);
    } else {
      $default = (empty($defaults[$i]['identifier']) ? NULL :
        $defaults[$i]['identifier']);
    }

    $idfield = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Identifier'),
      '#description'   => $this->t('Set the id to be used for this field, will default to the field type. If you map the same type to multiple subfields you must use unique id\'s'),
      '#default_value' => $default,
      '#required'      => TRUE,
    ];
    $idPos = array_merge($id, ['map', $i, 'identifier']);
    NestedArray::setValue($form, $idPos, $idfield);

  }

  /**
   * @param string $type
   * @param array  $defaults
   * @param array  $options
   */
  private function getDefaultSuggestion(string $type,
                                        array &$defaults,
                                        array $options) {

    if (!array_key_exists(0, $defaults) ||
        (array_key_exists(0, $defaults) && !isset($defaults[0]))
    ) {
      //Get a type suggestion for the first array member where there is no existing default value (ie is new)
      //If there is no type suggestion (and currently there won't be as this feature is WIP)
      //then return the first none 'none' value in the options array
      $defaults[0] = [];
      $defaults[0]['type'] = (count($options) > 1) ? array_values($options)[1] :
        NULL;

    }

  }

  /**
   * @param array                           $form
   * @param FieldConfig|BaseFieldDefinition $bundle_field
   * @param array                           $id
   * @param int                             $i
   */
  private function buildHiddenObjectFields(array &$form,
                                           $bundle_field,
                                           array $id,
                                           int $i) {

    //Field config is way more complicated because we can't guarantee anything about their structure
    //Ideally at this point we EITHER take a reference to another map, or build an inline field set
    $def = $bundle_field->getFieldStorageDefinition();

    //we should ALWAYS just refer to another map with these,
    //and make sure we ship with inbuilt types dealt with via install config
    if ($def->getType() === 'comment') {
      //Comments are the odd one out here, and currently we cant deal with them nicely
      //TODO - make comment mapping work
      return;
    }

    $select = [
      '#type'          => 'hidden',
      '#title'         => $this->t('Type'),
      '#description'   => $this->t('Entity Type'),
      '#default_value' => $def->getSetting('target_type'),
    ];
    $selectPos = array_merge($id, ['map', $i, 'target_type']);
    NestedArray::setValue($form, $selectPos, $select);

    $handler = $bundle_field->getSetting('handler_settings');
    $source = array_key_exists('target_bundles', $handler) ?
      reset($handler['target_bundles']) :
      ''; // TODO - allow multi mapping handling
    $select = [
      '#type'          => 'hidden',
      '#title'         => $this->t('Bundle'),
      '#description'   => $this->t('Entity Bundle'),
      '#default_value' => $source,
    ];
    $selectPos = array_merge($id, ['map', $i, 'target_bundle']);
    NestedArray::setValue($form, $selectPos, $select);

  }

  /**
   * @param array $form
   * @param array $id
   * @param int   $num
   */
  protected function addMappingButtons(array &$form,
                                       FormStateInterface $form_state,
                                       array $id,
                                       int $num) {
    $idString = $this->getIdString($id);

    $dynamic = (bool) $form_state->getValue('dynamic_mapping', FALSE);
    $mapDefault = NestedArray::getValue($form,
                                        array_merge($id,
                                                    [
                                                      'map',
                                                      0,
                                                      'type',
                                                      '#default_value',
                                                    ]));

    $disabled = $dynamic || $mapDefault === 'object';

    if (!$disabled) {
      $add = [
        '#type'     => 'submit',
        '#name'     => $idString . '_button',
        '#value'    => $this->t('Add mapping'),
        '#submit'   => ['Drupal\elastic_search\Form\FieldableEntityMapFormCallbacks::incrementMappingCountCallback'],
        '#disabled' => $disabled,
        '#ajax'     => [
          'callback' => 'Drupal\elastic_search\Form\FieldableEntityMapFormCallbacks::addMoreCallback',
          'wrapper'  => "field-wrapper-{$idString}",
        ],
      ];
      NestedArray::setValue($form, array_merge($id, ['add_mapping']), $add);
    }

    if ($num > 1) {
      $remove = [
        '#type'     => 'submit',
        '#value'    => $this->t('Remove one'),
        '#name'     => $idString . '_remove_button',
        '#submit'   => ['Drupal\elastic_search\Form\FieldableEntityMapFormCallbacks::decrementMappingCountCallback'],
        '#disabled' => $disabled,
        '#ajax'     => [
          'callback' => 'Drupal\elastic_search\Form\FieldableEntityMapFormCallbacks::removeCallback',
          'wrapper'  => "field-wrapper-{$idString}",
        ],
      ];
      NestedArray::setValue($form,
                            array_merge($id, ['remove_mapping']),
                            $remove);
    }
  }

  /**
   * @param array $id
   *
   * @return string
   */
  private function getIdString(array $id): string {
    return implode('__', $id);
  }

  /**
   * Filter out any fields that have multiple mappings set to none, as these
   * make no sense
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function filterExtraNone(FormStateInterface $form_state) {
    /*
     * Filter out any none entries from fields that have a cardinality > 1
     */
    $fields = $form_state->getValue('fields') ?? [];
    foreach ($fields as $id => $field) {
      $count = count($field['map']);
      if ($count > 1) {
        $filtered = array_filter($field['map'],
          function ($v) {
            return !($v === 'none' || $v === '');
          });
        $fields[$id]['map'] = array_values($filtered);
      }
    }
    $form_state->setValue('fields', $fields);
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->filterExtraNone($form_state);

    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();

    //If simple references are selected globally convert all reference types
    if ($form_state->getValue('simple_reference') === 1) {
      $form_state = $this->simpleReferenceHandler($form_state);
    }

    $converter = new ArrayKeyToCamelCaseHelper();
    $converted = $converter->convert($form_state->getValues());
    $form_state->setValues($converted);
    $this->entity = $this->buildEntity($form, $form_state);
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Form\FormStateInterface
   */
  protected function simpleReferenceHandler(FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values['fields'] as $index => $field) {
      $type = NestedArray::getValue($field, ['map', 0, 'type']);
      if ($type === 'object') {
        $values['fields'][$index]['map'][0] = [
          'type' => 'simple_reference',
        ];
      }
    }
    $form_state->setValues($values);
    return $form_state;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var FieldableEntityMapInterface $fieldableEntityMap */
    $fieldableEntityMap = $this->entity;

    $status = $fieldableEntityMap->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Fieldable entity map.',
                                    [
                                      '%label' => $fieldableEntityMap->label(),
                                    ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Fieldable entity map.',
                                    [
                                      '%label' => $fieldableEntityMap->label(),
                                    ]));
    }
  }

}
