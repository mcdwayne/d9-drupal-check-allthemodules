<?php

namespace Drupal\entity_modifier\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Entity modifier edit forms.
 *
 * @ingroup entity_modifier
 */
class EntityModifierForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\entity_modifier\Entity\EntityModifier */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

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
        drupal_set_message($this->t('Created the %label Entity modifier.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Entity modifier.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.entity_modifier.canonical', ['entity_modifier' => $entity->id()]);
  }

}
