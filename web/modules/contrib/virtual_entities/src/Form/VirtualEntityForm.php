<?php

namespace Drupal\virtual_entities\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Virtual entity edit forms.
 *
 * @ingroup virtual_entities
 */
class VirtualEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\virtual_entities\Entity\VirtualEntity */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Virtual entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Virtual entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.virtual_entity.canonical', ['virtual_entity' => $entity->id()]);
  }

}
