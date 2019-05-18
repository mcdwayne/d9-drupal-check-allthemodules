<?php

namespace Drupal\ansible\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Ansible entity edit forms.
 *
 * @ingroup ansible
 */
class AnsibleEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.ansible_entity.collection');
  }

}
