<?php
/**
 * @file Helper.php
 */

namespace Drupal\fieldclone;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Routing\RequestContext;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\TypedData;
use Drupal\field\Entity\FieldConfig;
use Drupal\paragraphs\Plugin\Field\FieldWidget\InlineParagraphsWidget;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\WidgetInterface;

class FieldCloner {

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public static function prepareEntity(EntityInterface $entity) {
    if ($querystring = self::getQueryString()) {
      $errors = [];
      $commands = self::parse($querystring, $entity, $errors);
      if ($errors) {
        foreach ($errors as $error) {
          drupal_set_message($error, 'error');
        }
        return;
      }

      /** @var \Drupal\replicate\Replicator $replicator */
      $replicator = \Drupal::service('replicate.replicator');

      // We did some checks in the parser but let's catch the rest here.
      try {
        /** @var FieldableEntityInterface $entity */
        foreach ($commands as $command) {
          /** @var FieldItemListInterface $source_field */
          /** @var FieldItemListInterface $target_field */
          list($source_field, $target_field) = $command;

          $replicator->cloneEntityField($source_field, $target_field);
        }
      } catch (\Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
      }
    }
  }

  /**
   * Get the query key.
   *
   * This can be made configurable later.
   *
   * @return string
   */
  public static function getQueryKey() {
    return 'fieldclone';
  }

  public static function getQueryString() {
    $request = \Drupal::request();
    $query = $request->query;
    if ($query) {
      return $query->get(self::getQueryKey());
    }
    else {
      return NULL;
    }
  }

  /**
   * Parse our query string.
   *
   * @param $string
   *  The query string. Syntax is like
   *   node/add/foo?paragraphs-preopen=field_par1:text|field_par1:image|field_par2:link
   * @param EntityInterface $target_entity
   *   THe entity to edit.
   * @param array $errors
   *   Errors returned by reference.
   * @return array
   *   An array of commands.
   */
  public static function parse($string, EntityInterface $target_entity, &$errors = NULL) {
    if (!$errors) {
      $errors = [];
    }
    $t_args = [
      '%par' => self::getQueryKey(),
    ];
    if (!is_string($string) || !$string) {
      $errors[] = t('Query parameter %par must be a nonempty string.', $t_args);
      return [];
    }
    if (!$target_entity instanceof FieldableEntityInterface) {
      $errors[] = t('Query parameter %par must only be used for fieldable entities.', $t_args);
      return [];
    }
    /** @var FieldableEntityInterface $target_entity */
    $t_args['%target_type'] = $target_entity->getEntityType();
    $t_args['%target_bundle'] = $target_entity->bundle();

    $chunks = explode('|', $string);
    $commands = [];
    foreach ($chunks as $chunk) {
      $t_args['%chunk'] = $chunk;
      $parts = explode(':', $chunk);
      if (count($parts) !== 3) {
        $errors[] = t('Query parameter %par: Can not understand %chunk.', $t_args);
        continue;
      }
      list($source_type, $source_id, $field_specs) = $parts;
      $t_args['%source_type'] = $source_type;
      $t_args['%source_id'] = $source_id;
      $t_args['%field_specs'] = $field_specs;


      // Check source entity.
      $entity_storage = \Drupal::entityManager()->getStorage($source_type);
      /** @var FieldableEntityInterface $source_entity */
      $source_entity = $entity_storage->load($source_id);
      $t_args['%source_bundle'] = $source_entity->bundle();

      if (!$source_entity) {
        $errors[] = t('Query parameter %par: %source_type with ID %source_id does not exist.', $t_args);
        continue;
      }

      if (!$source_entity->access('view')) {
        $errors[] = t('Query parameter %par: You do not have access to %source_type with ID %source_id.', $t_args);
        continue;
      }


      // Process the field specs.
      $field_specs_parts = preg_split('#(?=[ +-])#', $field_specs);
      if ($field_specs_parts === FALSE) {
        $field_specs_parts = [$field_specs];
      }
      foreach ($field_specs_parts as $field_spec) {

        // Special case: Field = "*"
        if ($field_spec === '*') {
          $fields = $source_entity->getFields();
          $entity_type_id = $source_entity->getEntityTypeId();
          $clonable_fields = self::filterClonableFields($fields, $entity_type_id);
          foreach ($clonable_fields as $source_field) {
            /** @var EntityReferenceFieldItemListInterface $source_field */
            $current_field_name = $source_field->getName();

            /** @var EntityReferenceFieldItemListInterface $target_field */
            $target_field = $target_entity->get($current_field_name);
            $commands[$target_field->getName()] = [$source_field, $target_field];
          }
        }
        else {
          // Parse negation & target field.
          preg_match('#^(?<negation>[ +-]?)(?<target>.*?)(=(?<source>.*))?$#', $field_spec, $matches);
          $remove_target_field = $matches['negation'] === '-';
          $target_field_name = $matches['target'];
          $source_field_name = !empty($matches['source']) ? $matches['source'] : $target_field_name;

          $t_args['%source_field'] = $source_field_name;
          $t_args['%target_field'] = $target_field_name;

          if (!$target_entity->hasField($target_field_name)) {
            $errors[] = t('Query parameter %par: %target_type bundle %target_bundle does not have target field %target_field.', $t_args);
            continue;
          }

          if ($remove_target_field) {
            unset($commands[$target_field_name]);
          }
          else {
            if (!$source_entity->hasField($source_field_name)) {
              $errors[] = t('Query parameter %par: %source_type bundle %source_bundle does not have source field %source_field.', $t_args);
              continue;
            }

            /** @var EntityReferenceFieldItemListInterface $source_field */
            $source_field = $source_entity->get($source_field_name);
            /** @var EntityReferenceFieldItemListInterface $target_field */
            $target_field = $target_entity->get($target_field_name);

            if (!self::fieldsCompatible($source_field, $target_field)) {
              $errors[] = t('Query parameter %par: %source_field of %source_type bundle %source_bundle does not (exactly!) match %target_field of %target_type bundle %target_bundle.', $t_args);
              continue;
            }

            if (!$source_field->access('view')) {
              $errors[] = t('Query parameter %par: You do not have access to field %source_field of %source_type with ID %source_id.', $t_args);
              continue;
            }
            $commands[$target_field_name] = [$source_field, $target_field];
          }
        }
      }
    }
    return $commands;
  }

  /**
   * Check if fields are compatible.
   *
   * @param FieldItemListInterface $source_field
   * @param FieldItemListInterface $target_field
   * @return bool
   */
  protected static function fieldsCompatible(FieldItemListInterface $source_field, FieldItemListInterface $target_field) {
    /** @var FieldItemDataDefinition $source_item_definition */
    $source_item_definition = $source_field->getItemDefinition();
    $source_type = $source_item_definition->getDataType();
    /** @var FieldItemDataDefinition $target_item_definition */
    $target_item_definition = $target_field->getItemDefinition();
    $target_type = $target_item_definition->getDataType();

    $compatible_type = $source_type == $target_type;

    $source_cardinality = $source_item_definition->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality();
    $target_cardinality = $target_item_definition->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality();
    $compatible_cardinality = ($target_cardinality == -1) ? TRUE : (
    ($source_cardinality == -1) ? FALSE : ($target_cardinality >= $source_cardinality)
    );

    return $compatible_type && $compatible_cardinality;
  }

  /**
   * @param array $fields
   * @param $entity_type_id
   * @return array
   */
  public static function filterClonableFields(array $fields, $entity_type_id) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $entity_keys = $entity_type->getKeys();
    $omitted_key_types = [
      'id' => TRUE,
      'revision' => TRUE,
      'bundle' => TRUE,
      'uuid' => TRUE
    ];
    $omitted_entity_keys = array_flip(array_intersect_key($entity_keys, $omitted_key_types));

    $clonable_fields = array_diff_key($fields, $omitted_entity_keys);
    return $clonable_fields;
  }

}
