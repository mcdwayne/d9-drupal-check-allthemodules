<?php

namespace Drupal\entity_reference_inline\Plugin\DataType;

use Drupal\Core\Entity\Plugin\DataType\EntityReference;

/**
 * Defines an 'entity_revision_inline_reference' data type.
 *
 * @DataType(
 *   id = "entity_inline_reference",
 *   label = @Translation("Entity reference inline"),
 *   definition_class = "\Drupal\Core\TypedData\DataReferenceDefinition"
 * )
 */
class EntityReferenceInline extends EntityReference {

  /**
   * {@inheritdoc}
   */
  public function getTarget() {
    if (!isset($this->target) && isset($this->id)) {
      if (($parent = $this->getParent()) && ($parent_entity = $parent->getEntity()) && $parent_entity->loadedUnchanged) {
        // If we have a valid reference, return the entity's TypedData adapter.
        //@todo this requires https://www.drupal.org/node/2620980
        $entity = \Drupal::entityTypeManager()->getStorage($this->getTargetDefinition()->getEntityTypeId())->loadUnchanged($this->id);

        // Flag the entity object we are comparing that the entity has been
        // loaded through loadUnchanged in order for the referenced entities hold
        // in an inline entity field type to be loaded unchanged as well.
        // @see \Drupal\entity_reference_revisions_inline\Plugin\Field\FieldType\EntityReferenceRevisionsInlineItem::preSave()
        $entity->loadedUnchanged = TRUE;

        $this->target = isset($entity) ? $entity->getTypedData() : NULL;
      }
    }
    if (!isset($this->target)) {
      parent::getTarget();
      if (isset($this->target) && isset($parent_entity) && !empty($parent_entity->entityReferenceInlineForm)) {
        $this->target->getValue()->entityReferenceInlineForm = TRUE;
      }
    }
    return $this->target;
  }

}
