<?php

namespace Drupal\formazing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Field formazing entity edit forms.
 *
 * @ingroup formazing
 */
class FieldFormazingEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\formazing\Entity\FieldFormazingEntity */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\formazing\Entity\FieldFormazingEntity */
    $entity = &$this->entity;
    $entity->setWeight(0);

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()
          ->addStatus($this->t('Created the %label Field formazing entity.', [
            '%label' => $entity->label(),
          ]));
        break;
      default:
        \Drupal::messenger()
          ->addStatus($this->t('Saved the %label Field formazing entity.', [
            '%label' => $entity->label(),
          ]));
    }

    $form_state->setRedirect('entity.field_formazing_entity.canonical', ['field_formazing_entity' => $entity->id()]);
  }

}
