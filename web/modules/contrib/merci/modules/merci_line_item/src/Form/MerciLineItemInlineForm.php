<?php

namespace Drupal\merci_line_item\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Node inline form handler.
 */
class MerciLineItemInlineForm extends EntityInlineForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $labels = [
      'singular' => $this->t('item'),
      'plural' => $this->t('items'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $display = entity_get_display('merci_line_item', 'merci_reservation', 'inline_entity_form');
    $fields = [];
    $bundle = reset($bundles);
    $definitions = $this->entityFieldManager->getFieldDefinitions($this->entityType->id(), $bundle);
    if ($display) {
      foreach ($display->getComponents() as $name => $component) {
        if (array_key_exists($name, $definitions) and ($field_config = $definitions[$name])) {
          $label = $definitions[$name]->getLabel();
          $fields[$name] = [
            'type' => 'field',
            'label' => $label,
            'weight' => $component['weight'],
          ];
        }

      }
    }
    else {
      return parent::getTableFields($bundles);
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    if ($entity_form['#op'] == 'add') {

      // Set default date to first entity.
      $entities = $form_state->get(['inline_entity_form', $entity_form['#ief_id'], 'entities']);
      if (count($entities)) {
        $entity = reset($entities);
        $entity_form['#entity']->merci_reservation_date = $entity['entity']->merci_reservation_date;
      }

    }
    else {
      $entity_form['#form_mode'] = 'default';
    }
    $entity_form = parent::entityForm($entity_form, $form_state);
    // Remove the "Revision log" textarea,  it can't be disabled in the
    // form display and doesn't make sense in the inline form context.
    $entity_form['revision_log_message']['#access'] = FALSE;
    return $entity_form;
  }

}
