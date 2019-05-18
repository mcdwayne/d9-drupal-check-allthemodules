<?php

namespace Drupal\private_entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\private_entity\Plugin\Field\FieldType\PrivateEntityItem;

/**
 * Class PrivateEntity.
 */
class PrivateEntity implements PrivateEntityInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PrivateEntity object.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function initExistingEntities($entity_type_id, $entity_bundle, $field_name) {
    $updated = 0;
    // @todo inject logger and messenger
    $logger = \Drupal::logger('private_entity');
    $messenger = \Drupal::messenger();
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $bundleKey = $storage->getEntityType()->getKey('bundle');
      $entityQuery = \Drupal::entityQuery($entity_type_id);
      $entityQuery->condition($bundleKey, $entity_bundle);
      $entityIds = $entityQuery->execute();

      $updated = 0;
      foreach ($entityIds as $entityId) {
        $entity = $storage->loadUnchanged($entityId);
        if ($entity instanceof ContentEntityInterface) {
          // @todo review multilingual
          // @todo wait for field being created
          $entity->set($field_name, PrivateEntityItem::ACCESS_PUBLIC);
          if ($entity->save() === SAVED_UPDATED) {
            ++$updated;
          }
        }
      }
    }
    catch (PluginNotFoundException $exception) {
      $logger->error($exception->getMessage());
      $messenger->addError($exception->getMessage());
    }
    catch (InvalidPluginDefinitionException $exception) {
      $logger->error($exception->getMessage());
      $messenger->addError($exception->getMessage());
    }
    catch (EntityStorageException $exception) {
      $logger->error($exception->getMessage());
      $messenger->addError($exception->getMessage());
    }

    return $updated;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNameFromType(FieldableEntityInterface $entity, $field_type) {
    $result = NULL;
    if (!empty($field_type)) {
      $fieldDefinitions = $entity->getFieldDefinitions();
      foreach ($fieldDefinitions as $fieldDefinition) {
        if ($fieldDefinition instanceof FieldConfig) {
          if ($fieldDefinition->getType() === $field_type) {
            $result = $fieldDefinition->getName();
          }
        }
      }
    }
    return $result;
  }

}
