<?php

namespace Drupal\blizz_bulk_creator\Services;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\media_entity\MediaInterface;

/**
 * Class BulkcreateService.
 *
 * Custom service performing the bulkcreate operations.
 *
 * @package Drupal\blizz_bulk_creator\Services
 */
class BulkcreateService implements BulkcreateServiceInterface {

  /**
   * Drupal's entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The currently (possibly logged-in) user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Custom service to ease the handling of media entities.
   *
   * @var \Drupal\blizz_bulk_creator\Services\EntityHelperInterface
   */
  protected $entityHelper;

  /**
   * Custom service to ease administrative tasks.
   *
   * @var \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface
   */
  protected $administrationHelper;

  /**
   * The storage interfaces used during processing.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface[]
   */
  protected $storageInterfaces;

  /**
   * The entity type definition interfaces for the entity types processed.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $entityDefinitionInterfaces;

  /**
   * BulkcreateService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Drupal's entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currently (possibly logged-in) user.
   * @param \Drupal\blizz_bulk_creator\Services\EntityHelperInterface $entity_helper
   *   Custom service to ease the handling of media entities.
   * @param \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface $administration_helper
   *   Custom service to ease administrative tasks.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    EntityHelperInterface $entity_helper,
    BulkcreateAdministrationHelperInterface $administration_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->entityHelper = $entity_helper;
    $this->administrationHelper = $administration_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function initializeBulkcreations(array $form, FormStateInterface $form_state) {

    // Get the user entered input of the form. Somehow, the
    // values don't end up in ->getValues() - but since this
    // way of implementation is only temporary, we'll stick
    // with ->getUserInput().
    $input = $form_state->getUserInput();

    // Get the host entity for the bulkcreate data.
    $entity = $form_state->getFormObject() instanceof EntityFormInterface
      ? $form_state->getFormObject()->getEntity()
      : FALSE;

    // Extract all bulkcreate data from the input.
    $bulkcreations = array_filter(
      $input,
      function ($key) {
        return preg_match('~^bulkcreation_[0-9a-z\-_]+$~', $key);
      },
      ARRAY_FILTER_USE_KEY
    );

    // Inject the bulkcreateUsage into the bulkcreations found (they
    // are contained within ->getValues()).
    $values = $form_state->getValues();
    foreach (array_keys($bulkcreations) as $bulkcreation) {
      $bulkcreations[$bulkcreation]['bulkcreateUsage'] = $values[$bulkcreation]['bulkcreateUsage'];
    }

    // Instantiate the service and process the bulkcreations found.
    $bulkcreateService = \Drupal::service('blizz_bulk_creator.bulkcreate_service');
    $bulkcreateService->process($entity, $bulkcreations, $form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $entity, array $bulkcreations, array $form, FormStateInterface $form_state) {

    // Process each bulkcreation separately.
    foreach ($bulkcreations as $bulkcreation) {

      // Extract the usage configuration from the data array.
      /* @var \Drupal\blizz_bulk_creator\Entity\BulkcreateUsage $bulkcreateUsage */
      $bulkcreateUsage = $bulkcreation['bulkcreateUsage'];

      // Determine the bulkcreate configuration.
      $bulkcreateConfiguration = $bulkcreateUsage->getBulkcreateConfiguration();

      // Determine the target information of this bulkcreation.
      $targetBundle = $bulkcreateConfiguration->get('target_bundle');
      $bulkcreateField = $bulkcreateConfiguration->get('bulkcreate_field');

      // Determine the bulkcreate field definition.
      $bulkcreateFieldDefinition = $this->entityHelper->getBundleFields('media', $targetBundle)[$bulkcreateField];

      // Extract the bulkcreate data.
      // TODO
      // - we need to delegate the data extraction to the
      //   actual widget implementation.
      $bulkcreateData = $this->extractData(
        $bulkcreateFieldDefinition,
        $bulkcreation[$bulkcreateField]
      );

      // Extract the default values (if any).
      $defaultValues = [];
      if (!empty($defaultValueProvider = $bulkcreateConfiguration->getDefaultPropertyFields())) {

        // Extract each default value and store it to the default value array.
        foreach ($defaultValueProvider as $defaultValueFieldName => $defaultValueFieldDefinition) {
          // TODO
          // - we need to delegate the data extraction to the
          //   actual widget implementation.
          $defaultValues[$defaultValueFieldName] = $this->extractData(
            $defaultValueFieldDefinition,
            $bulkcreation['defaults'][$defaultValueFieldName]
          );
        }

      }

      // Prepare an array to hold the newly created media entities.
      $media_entities = [];

      // Create the media entities one by one.
      foreach (array_values($bulkcreateData) as $delta => $createItem) {

        // Determine the name for the generated entities.
        $name = $bulkcreateConfiguration->get('custom_entity_name') && !empty(trim($bulkcreation['entity_name_prefix']))
          ? sprintf(
              '%1$s %2$d (%3$s)',
              trim($bulkcreation['entity_name_prefix']),
              $delta + 1,
              $bulkcreateConfiguration->label()
            )
          : t(
              'Bulk-generated item @delta (@bulkcreation)',
              [
                '@delta' => $delta + 1,
                '@bulkcreation' => $bulkcreateConfiguration->label(),
              ]
            );

        // Base for each entity:
        // - bundle.
        // - name.
        // - Owner (User ID).
        // - status flag.
        // - the bulkcreate field (unique to all created entities).
        $media_entity = $this->createEntity(
          'media',
          $targetBundle,
          [
            'name' => $name,
            'uid' => $this->currentUser->id(),
            'status' => 1,
            $bulkcreateField => $createItem,
          ]
        );

        // Set the default values on the media entity.
        foreach ($defaultValues as $fieldname => $value) {
          $media_entity->set($fieldname, $value);
        }

        // Save the media entity.
        $media_entity->save();

        // Add the readily created entity to the result array.
        $media_entities[] = $media_entity;

      }

      // Determine the target field information for the bulkcreate data.
      $targetFieldInformation = $this->administrationHelper->getStructuredBulkcreateTargetFieldArray(
        $bulkcreateUsage->get('entity_type_id'),
        $bulkcreateUsage->get('bundle'),
        $bulkcreateUsage->get('target_field')
      );

      // Which stage should get multi-instantiated?
      $multi_stage = $bulkcreateUsage->get('multi_stage');

      // Determine which part of the target definition is to be instantiated
      // once and which part is the multi-instantiated part.
      $singleStages = array_slice($targetFieldInformation, 0, $multi_stage);
      $multiStages = array_slice($targetFieldInformation, $multi_stage);

      // Keep in mind which entity the actual host entity
      // is for the subentities to come.
      $host_entity = $entity;

      // First, process the single stages (can't be the
      // bulkcreate target itself).
      foreach ($singleStages as $stage => $stage_definition) {

        // Create the required subentity.
        $subentity = $this->createEntity(
          $stage_definition->target_entity_type_id,
          $stage_definition->target_bundle,
          [
            ($this->getEntityKey($stage_definition->target_entity_type_id, 'uid') ?: 'uid') => $this->currentUser->id(),
            ($this->getEntityKey($stage_definition->target_entity_type_id, 'status') ?: 'status') => 1,
          ]
        );

        // Set the hot entity to reference this subentity.
        $host_entity->set($stage_definition->fieldname, $subentity);

        // Set the new subentity as the host entity for subsequent subentities.
        $host_entity = $subentity;

      }

      // If the multistages only consist of the bulkcreate target itself,
      // this field holds all references to all media entities created.
      if (count($multiStages) === 1) {

        // Get the stage definition of the media stage.
        $stage_definition = array_shift($multiStages);

        // Get potentionally pre-filled values.
        $values = $host_entity->{$stage_definition->fieldname}->getValue();

        // Merge the new bulkcreated items into this array.
        $values = array_merge(
          $values,
          $media_entities
        );

        // Set the new field value.
        $host_entity->set($stage_definition->fieldname, $values);

      }
      // The are more than a single stage involved,
      // so build up the stack of subentities.
      else {

        // Get potentionally pre-existing field values.
        $stack = $host_entity->{$multiStages[0]->fieldname}->getValue();

        // Bulk-create the items.
        foreach ($media_entities as $media_entity) {
          $stack[] = $this->createBranch($multiStages, $media_entity);
        }

        // Set the enhanced stack.
        $host_entity->set($multiStages[0]->fieldname, $stack);

      }

      // Save the base entity.
      $entity->save();

    }

  }

  /**
   * Proxy function to more specialized methods.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field_config
   *   The field definition of the field to extract data from.
   * @param array $data
   *   The complete data array for that field.
   *
   * @return array
   *   The preprocessed data for that field.
   */
  private function extractData(FieldConfigInterface $field_config, array $data) {

    switch ($field_config->get('field_type')) {

      case 'image':
      case 'file':
        return $this->extractFileData($field_config, $data);

      case 'string':
      case 'string_long':
      case 'text_long':
        return $this->extractTextData($field_config, $data);

      case 'entity_reference':
        return $this->extractReferenceData($field_config, $data);

    }

  }

  /**
   * Extracts data for file-type fields.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field_config
   *   The field definition of the field to extract data from.
   * @param array $data
   *   The complete data array for that field.
   *
   * @return array
   *   The preprocessed data for that field.
   */
  private function extractFileData(FieldConfigInterface $field_config, array $data) {
    $result = [];
    foreach ($data['widget'] as $item) {
      if (isset($item['fids']) && !empty($item['fids'])) {
        $result[] = $item['fids'];
      }
    }
    $result = array_map(
      function ($fid) {
        return $this->getEntityStorageInterface('file')->load($fid);
      },
      array_combine(array_values($result), array_values($result))
    );
    return $result;
  }

  /**
   * Extracts data for reference-type fields.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field_config
   *   The field definition of the field to extract data from.
   * @param array $data
   *   The complete data array for that field.
   *
   * @return array
   *   The preprocessed data for that field.
   */
  private function extractReferenceData(FieldConfigInterface $field_config, array $data) {
    $result = [];
    $items = array_map(
      function ($item) {
        if (preg_match_all('~(["\'])(.*?)\1~i', $item, $matches)) {
          $terms = array_values($matches[2]);
          array_walk(
            $matches[0],
            function ($term) use (&$item) {
              $item = preg_replace(sprintf('~%s~', preg_quote($term, '~')), '', $item);
            }
          );
          $rest = explode(',', $item);
          $terms = array_merge(
            $terms,
            array_filter(
              $rest,
              function ($string) {
                return !empty(trim($string));
              }
            )
          );
          return array_map(
            function ($term) {
              return trim($term);
            },
            $terms
          );
        }
        else {
          return explode(',', trim($item));
        }
      },
      array_map(
        function ($item) {
          if (is_array($item) && isset($item['target_id'])) {
            return (int) $item['target_id'];
          }
          elseif (is_numeric($item)) {
            return (int) $item;
          }
          else {
            return $item;
          }
        },
        $data['widget']
      )
    );
    $terms = [];
    array_walk_recursive(
      $items,
      function ($a) use (&$terms) {
        $terms[] = trim($a);
      }
    );
    $terms = array_filter($terms);
    foreach ($terms as $item) {
      if (
        preg_match('~\(([1-9][0-9]*?)\)$~', $item, $matches) &&
        !empty($term = $this->entityTypeManager->getStorage('taxonomy_term')->load($matches[1]))
      ) {
        $result[] = $term;
      }
      elseif (
        is_numeric($item) &&
        !empty($term = $this->entityTypeManager->getStorage('taxonomy_term')->load($item))
      ) {
        $result[] = $term;
      }
      elseif (
        ($handler_settings = $field_config->getSetting('handler_settings')) &&
        isset($handler_settings['auto_create']) &&
        $handler_settings['auto_create'] === TRUE
      ) {
        $target_bundles = array_keys($handler_settings['target_bundles']);
        $term = $this->createEntity(
          'taxonomy_term',
          array_shift($target_bundles),
          ['name' => $item]
        );
        $term->save();
        $result[] = $term;
      }
    }
    return $result;
  }

  /**
   * Extracts data from text-type fields.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field_config
   *   The field definition of the field to extract data from.
   * @param array $data
   *   The complete data array for that field.
   *
   * @return array
   *   The preprocessed data for that field.
   */
  private function extractTextData(FieldConfigInterface $field_config, array $data) {
    $result = [];
    foreach ($data['widget'] as $item) {
      $result[] = $item['value'];
    }
    return $result;
  }

  /**
   * Creates an entity with given values.
   *
   * @param string $entity_type_id
   *   The name of the entity type to create.
   * @param string $bundle
   *   The name of the bundle the created entity should belong to.
   * @param array $values
   *   Optional array of values for the created entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity.
   */
  private function createEntity($entity_type_id, $bundle, array $values = []) {
    $entity = $this->getEntityStorageInterface($entity_type_id)->create(
      [$this->getEntityKey($entity_type_id, 'bundle') => $bundle]
    );
    foreach ($values as $key => $value) {
      if ($entity instanceof FieldableEntityInterface && $entity->hasField($key)) {
        $entity->set($key, $value);
      }
    }
    return $entity;
  }

  /**
   * Creates a single branch of the multiinstantiated stages.
   *
   * @param array $branchDefinition
   *   The branch definition.
   * @param \Drupal\media_entity\MediaInterface $media_entity
   *   The media entity to reference.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The "root" sub-entity of the created branch.
   */
  private function createBranch(array $branchDefinition, MediaInterface $media_entity) {
    foreach ($branchDefinition as $stage => $definition) {
      if (!$definition->isMediaField) {
        $subentity = $this->createEntity(
          $definition->target_entity_type_id,
          $definition->target_bundle
        );
        if (isset($hostentity)) {
          $hostentity->set($definition->fieldname, $subentity);
        }
        $hostentity = $subentity;
      }
      else {
        // Since the sub-entity holding the reference to the media entity
        // will never be the first in line, there will be a variable named
        // "hostentity".
        $hostentity->set($definition->fieldname, $media_entity);
      }
      // Remeber the subentity of the first stage to return as
      // the root entity of this branch.
      if ($stage == 0) {
        $result = $subentity;
      }
    }
    return $result;
  }

  /**
   * Get the entity storage interface (singleton).
   *
   * @param string $entity_type
   *   The entity type the storage interface should be returned for.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage interface for the given entity type.
   */
  private function getEntityStorageInterface($entity_type) {
    return isset($this->storageInterfaces[$entity_type])
      ? $this->storageInterfaces[$entity_type]
      : ($this->storageInterfaces[$entity_type] = $this->entityTypeManager->getStorage($entity_type));
  }

  /**
   * Get the entity definition (singleton).
   *
   * @param string $entity_type
   *   The entity type the definition should be returned for.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type interface for the given entity type.
   */
  private function getEntityDefinition($entity_type) {
    return isset($this->entityDefinitionInterfaces[$entity_type])
      ? $this->entityDefinitionInterfaces[$entity_type]
      : ($this->entityDefinitionInterfaces[$entity_type] = $this->entityTypeManager->getDefinition($entity_type));
  }

  /**
   * Returns the requested key name of a given entity type.
   *
   * @param string $entity_type
   *   The entity type in question.
   * @param string $name
   *   The name of the key to return.
   *
   * @return bool|string
   *   The value of the key for the given entity type or FALSE,
   *   if the entity type does not exist.
   */
  private function getEntityKey($entity_type, $name) {
    return !empty($definition = $this->getEntityDefinition($entity_type)) && $definition->hasKey($name)
      ? $definition->getKey($name)
      : FALSE;
  }

}
