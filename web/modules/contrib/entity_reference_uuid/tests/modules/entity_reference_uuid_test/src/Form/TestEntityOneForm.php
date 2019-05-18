<?php

namespace Drupal\entity_reference_uuid_test\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Test entity one edit forms.
 *
 * @ingroup entity_reference_uuid_test
 */
class TestEntityOneForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Test entity one.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Test entity one.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.test_entity_one.canonical', ['test_entity_one' => $entity->id()]);
  }

}
