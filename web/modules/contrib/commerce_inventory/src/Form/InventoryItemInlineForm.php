<?php

namespace Drupal\commerce_inventory\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Defines the inline form for purchasable entities.
 */
class InventoryItemInlineForm extends EntityInlineForm {

  /**
   * {@inheritdoc}
   */
  public function isTableDragEnabled($element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormValidate(array &$entity_form, FormStateInterface $form_state) {
    parent::entityFormValidate($entity_form, $form_state);

    // Perform entity validation only if the inline form was submitted,
    // skipping other requests such as file uploads.
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#ief_submit_trigger'])) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $entity_form['#entity'];

      // Get location violation.
      $violations = $entity->validate();
      $violation_list = $violations->getByField('location_id');
      if ($violation_list->has(0)) {
        $entity_form['actions']['ief_remove_confirm']['#parents'][] = 'ief_remove_confirm';
        $entity_form['actions']['ief_remove_confirm']['#attributes']['class'][] = 'error';
        $form_state->setError($entity_form['actions']['ief_remove_confirm'], $violation_list->get(0)->getMessage());
      }

      // @todo Checkout ajax to set nested message. \Drupal\file\Element\ManagedFile:uploadAjaxCallback
    }

  }

}
