<?php

namespace Drupal\contentserialize\Normalizer;

use Drupal\contentserialize\Event\ImportEvents;
use Drupal\contentserialize\Event\MissingReferenceEvent;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\serialization\EntityResolver\EntityResolverInterface;
use Drupal\serialization\EntityResolver\UuidReferenceInterface;
use Drupal\serialization\Normalizer\FieldItemNormalizer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * (De)-normalizes entity reference fields replacing serial IDs with UUIDs.
 *
 * It uses a UUID resolver rather than an entity repository.
 *
 * @see \Drupal\serialization\EntityResolver\UuidResolver
 */
class UuidEntityReferenceFieldItemNormalizer extends FieldItemNormalizer implements UuidReferenceInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = EntityReferenceItem::class;

  /**
   * The UUID entity resolver.
   *
   * @var \Drupal\serialization\EntityResolver\EntityResolverInterface
   */
  protected $uuidResolver;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Create a UuidEntityReferenceFieldItemDenormalizer.
   *
   * @param \Drupal\serialization\EntityResolver\EntityResolverInterface $uuid_resolver
   *   The UUID entity resolver.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(
    EntityResolverInterface $uuid_resolver,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->uuidResolver = $uuid_resolver;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = parent::normalize($field_item, $format, $context);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $field_item->get('entity')->getValue();
    if (!$entity) {
      return $values;
    }

    $values['target_type'] = $entity->getEntityTypeId();
    $uuid = $entity->uuid();
    if ($uuid) {
      unset($values['target_id'], $values['target_revision_id']);
      $values['target_uuid'] = $uuid;
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    // @todo Do I need the check on target_id below?
    if (!isset($data['target_id'])) {
      $id = $this->uuidResolver->resolve($this, $data, $data['target_type']);
      if ($id) {
        $data['target_id'] = $id;
      }
      else {
        /** @var EntityReferenceItem $field */
        $field = $context['target_instance'];
        $entity = $field->getEntity();
        $field_name = $field->getFieldDefinition()->getName();
        $field_type = $field->getFieldDefinition()->getType();
        $delta = $field->getName();
        // Creating a closure for every delta isn't the quickest or most memory
        // efficient method, but it's flexible and understandable.
        // @todo Remove the special casing of entity reference revisions once
        //   #2667748 lands.
        if ($field_type == 'entity_reference_revisions') {
          $update_reference = function (ContentEntityInterface $entity, $target_id, $target_vid) use ($field_name, $field_type, $delta) {
            $entity->{$field_name}[$delta] = [
              'target_id' => $target_id,
              'target_revision_id' => $target_vid,
            ];
          };
        }
        else {
          $update_reference = function (ContentEntityInterface $entity, $target_id, $target_vid) use ($field_name, $field_type, $delta) {
            $entity->{$field_name}[$delta] = $target_id;
          };
        }

        $event = new MissingReferenceEvent(
          $entity->getEntityTypeId(),
          $entity->uuid(),
          $data['target_type'],
          $data['target_uuid'],
          $update_reference,
          $context
        );
        $this->eventDispatcher->dispatch(ImportEvents::MISSING_REFERENCE, $event);
      }
    }
    return parent::constructValue($data, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid($data) {
    return empty($data['target_id']) ? NULL : $data['target_id'];
  }

}
