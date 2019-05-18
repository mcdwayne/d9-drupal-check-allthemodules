<?php

namespace Drupal\bridtv\Field;

use Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraint;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BridtvReferenceFieldItemList.
 */
class BridtvReferenceFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    /** @var \Symfony\Component\Validator\Constraint $constraint */
    foreach ($constraints as $index => $constraint) {
      if ($constraint instanceof ValidReferenceConstraint) {
        unset($constraints[$index]);
      }
    }
    $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    $constraint_manager->create('BridtvValidReference', []);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    // This is basically a copy-paste from EntityReferenceFieldItemList.
    // It needed changes regards defensive checks for the target id.
    // Otherwise, it would fail hard when trying to save field storage settings.
    // Also, creating new entities is not needed here,
    // as media items are being handled via synchronization.
    $grandparent = get_parent_class(get_parent_class($this));
    $default_value = $grandparent::defaultValuesFormSubmit($element, $form, $form_state);

    $ids = [];
    foreach ($default_value as $delta => $properties) {
      if (isset($default_value[$delta]['target_id'])) {
        $ids[] = $default_value[$delta]['target_id'];
      }
    }
    $entities = [];
    if (!empty($ids)) {
      $entities = \Drupal::entityTypeManager()
        ->getStorage($this->getSetting('target_type'))
        ->loadMultiple($ids);
    }

    // Convert numeric IDs to UUIDs to ensure config deployability.
    foreach ($default_value as $delta => $properties) {
      unset($default_value[$delta]['target_id']);
      if (isset($properties['target_id']) && isset($entities[$properties['target_id']])) {
        $default_value[$delta]['target_uuid'] = $entities[$properties['target_id']]->uuid();
      }
    }
    return $default_value;
  }

}
