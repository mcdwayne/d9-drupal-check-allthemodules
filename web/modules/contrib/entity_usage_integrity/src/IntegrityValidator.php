<?php

namespace Drupal\entity_usage_integrity;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationCollections;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity usage integrity validator service.
 */
final class IntegrityValidator implements EntityHandlerInterface {
  use StringTranslationTrait;

  /**
   * The entity usage service.
   *
   * @var \Drupal\entity_usage_integrity\EntityUsage
   */
  protected $entityUsage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity usage integrity logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Construct the IntegrityValidator object.
   *
   * @param \Drupal\entity_usage_integrity\EntityUsage $entity_usage
   *   The entity usage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The entity usage integrity logger channel.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(EntityUsage $entity_usage, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, MessengerInterface $messenger, Renderer $renderer) {
    $this->entityUsage = $entity_usage;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_usage_integrity.usage'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.entity_usage_integrity'),
      $container->get('messenger'),
      $container->get('renderer')
    );
  }

  /**
   * Validate usage integrity for given entity and get validated relations.
   *
   * No matter of context, we are validating relations on which current entity
   * is target.
   * Extra check happens on:
   *  - CONTEXT_EDIT_FORM_VIEW: when we are opening entity edit form,
   *    we also check connections, where current entity is source of relation,
   *  - CONTEXT_ENTITY_SAVE: before entity save, we have to check if new
   *    entity usage connections are valid. This check is done on future
   *    relations, so we can't do standard target check based on current state
   *    of entity usage, but we have to get relations directly from
   *    entity field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $current_entity
   *   Entity for which we are validating entity usage integrity.
   * @param string $context
   *   Context of validation.
   *
   * @return \Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationCollections
   *   Collection of relations for current entity with statuses.
   */
  public function getValidatedUsageRelations(EntityInterface $current_entity, $context) {
    // Create collection for relations.
    $relations = new RelationCollections();

    // Check current entity relations with sources.
    $sources = $this->entityUsage->listDefaultRevisionsForSources($current_entity);
    $this->validateEntityRelations($current_entity, $sources, 'target', $relations);

    // Depending on context, extra validation may be required.
    if ($context === IntegrityValidationContext::EDIT_FORM_VIEW) {
      // Check current entity relations with targets.
      $targets = $this->entityUsage->listDefaultRevisionsForTargets($current_entity);
      $this->validateEntityRelations($current_entity, $targets, 'source', $relations);
    }
    // TODO consider to cleanup the code and don't check that.
    //   Drupal core already offers this, in 2 forms:
    //    - the entity reference widget does not provide autocomplete
    //      suggestions for unpublished entities
    //    - a validation constraint (= blocks the saving, which is exactly
    //      what we want when referencing unpublished entities)
    //   This will require code cleanup in few other places.
    // By now I'm disabling that check just by commenting that part of code.
    // In future code cleanup will be required for that.
    /*elseif ($context === IntegrityValidationContext::ENTITY_SAVE) {
      // Before entity save, new relations are not present on entity usage
      // table and we can't do check like on context EDIT_FORM_VIEW.
      // We have to get new relations directly from referencing fields.
      $targets = $this->entityUsage->listDefaultRevisionsForTargetsFromFields($current_entity);
      $this->validateEntityRelations($current_entity, $targets, 'source', $relations);
    }*/

    return $relations;
  }

  /**
   * Validate relation between current entity and related entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $current_entity
   *   Entity for which we are validating entity usage integrity.
   * @param array $related_entities_data
   *   Data about related entities to current entity.
   * @param string $relation_type
   *   Relation type between current entity and related entities.
   * @param \Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationCollections $relations
   *   Collection of validated relations.
   */
  protected function validateEntityRelations(EntityInterface $current_entity, array $related_entities_data, $relation_type, RelationCollections $relations) {
    $broken_relations = [];
    foreach ($related_entities_data as $related_entity_type => $related_entity_ids) {
      $related_entities = $this->entityTypeManager->getStorage($related_entity_type)->loadMultiple($related_entity_ids);
      foreach ($related_entity_ids as $related_entity_id) {
        $related_entity = isset($related_entities[$related_entity_id]) ? $related_entities[$related_entity_id] : NULL;
        // If relation is inactive (e.g. unpublished source -> published target)
        // there is no need for further processing. This is indicated when
        // isValid() returns NULL.
        if (($relation_type === 'source' && ($status = self::getStatus($current_entity, $related_entity)) !== 'ignore') ||
          ($relation_type === 'target' && ($status = self::getStatus($related_entity, $current_entity)) !== 'ignore')) {
          $relations->getRelationCollectionWithStatus($status)->add($current_entity, $related_entity, $relation_type);
        }
        // TODO consider to that elseif logic to IntegrityValidationTrait.
        // TODO then use RelationItem here to store also broken relations.
        // TODO (now we are storing only valid/invalid relations).
        elseif ($relation_type === 'source' && $related_entity === NULL) {
          $broken_relations[] = '/' . $related_entity_type . '/' . $related_entity_id;
        }
      }
    }
    // TODO consider to move that if logic to IntegrityValidationTrait.
    // TODO then use RelationItem to store also broken relations.
    // If there are any broken relations (relation exists on entity usage,
    // but target entity doesn't exist), we wil display that to the user
    // and log. It's not a blocker for us, as we can't do nothing with that,
    // as content editor (this should be auto fixed after entity save).
    // But it may be good to have that knowledge and restore missing entities
    // and then fix relations.
    if (!empty($broken_relations)) {
      $message = [
        '#markup' => $this->t('Broken relation found in <a href=":url">@title</a> to non-existing:', [
          ':url' => $current_entity->toUrl()->toString(),
          '@title' => $current_entity->label(),
        ]),
        'list' => [
          '#theme' => 'item_list',
          '#items' => $broken_relations,
        ],
      ];

      $this->logger->error($this->renderer->renderPlain($message));
      $this->messenger->addWarning($message);
    }

  }

  /**
   * Validate integrity between source and target entity.
   *
   * Relation is 'valid' if both entities exists and both are published.
   * Relation is 'invalid' if target entity is unpublished.
   * Relation is 'broken' if target entity doesn't exists.
   * Relation is 'ignore' if it is valid, but source is unpublished.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   Source entity.
   * @param \Drupal\Core\Entity\EntityInterface|null $target_entity
   *   Target entity.
   *
   * @return string
   *   Relation is 'valid' if both entities exists and both are published.
   *   Relation is 'invalid' if target entity is unpublished.
   *   Relation is 'broken' if target entity doesn't exists.
   *   Relation is 'ignore' if it is valid, but source is unpublished.
   */
  protected static function getStatus(EntityInterface $source_entity = NULL, EntityInterface $target_entity = NULL) {
    // If source of relation is unpublished, relation is valid but inactive.
    // No need to take any action.
    if (!self::isPublished($source_entity)) {
      return 'ignore';
    }
    // If target of relation is NULL, relation is broken, as it no longer exists
    // but source still referes to it.
    if ($target_entity == NULL) {
      // TODO this is temporary return value, it will be replaced with
      // TODO return 'broken' when TOOD from lines 166-168 will be implemented.
      return 'ignore';
      //return 'broken';
    }
    // If source of relation is published,
    // relation is valid only if target is published too.
    return self::isPublished($target_entity) ? 'valid' : 'invalid';
  }

  /**
   * Check if given entity is published.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to check status.
   *
   * @return bool
   *   TRUE if entity is published, FALSE otherwise.
   */
  protected static function isPublished(EntityInterface $entity = NULL) {
    // If entity doesn't exists, skip that relation.
    // We can't give any information about that relation as one
    // of entities in it doesn't exists.
    if ($entity === NULL) {
      return NULL;
    }
    elseif ($entity instanceof EntityPublishedInterface) {
      return $entity->isPublished();
    }
    // By default if entity exists and is not instance
    // of EntityPublishedInterface, we assume it is published.
    return TRUE;
  }

}
