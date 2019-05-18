<?php

namespace Drupal\field_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;

/**
 * Adds a readonly field widget.
 *
 * @todo Expand this to boolean, ER etc. fields.
 *
 * @FieldWidget(
 *   id = "readonly_entity_reference",
 *   label = @Translation("Readonly text field"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions",
 *   },
 * )
 */
class ReadonlyEntityReference extends Readonly {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $property_name = $items[$delta]->mainPropertyName();

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if ($entity = $items[$delta]->entity) {
      if ($items instanceof EntityReferenceRevisionsFieldItemList && $entity instanceof RevisionableInterface) {
        $element[$property_name]['#value'] = "{$entity->label()} ({$entity->getRevisionId()})";
      }
      else {
        $element[$property_name]['#value'] = "{$entity->label()} ({$entity->id()})";
      }
    }

    return $element;
  }

}
