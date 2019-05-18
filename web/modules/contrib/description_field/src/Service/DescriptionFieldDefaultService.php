<?php

namespace Drupal\description_field\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class DescriptionFieldDefaultService.
 */
class DescriptionFieldDefaultService implements DescriptionFieldDefaultServiceInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterFieldStorageConfigEditForm(array &$form, FormStateInterface $form_state, $form_id) {
    $storage = $form_state->getFormObject()->getEntity();
    $field_type = $storage->getType();
    if ($field_type == 'description_field') {
      $form['cardinality_container']['#access'] = FALSE;
      $form['cardinality_information'] = [
        '#type' => 'item',
        '#title' => $this->t('Note: this field can only have one value.'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldConfigEditForm(array &$form, FormStateInterface $form_state, $form_id) {
    $storage = $form_state->getFormObject()->getEntity()->getFieldStorageDefinition();
    $field_type = $storage->getType();
    if ($field_type == 'description_field') {
      unset($form['description']);
      unset($form['required']);
      unset($form['default_value']);
    }
  }

}
