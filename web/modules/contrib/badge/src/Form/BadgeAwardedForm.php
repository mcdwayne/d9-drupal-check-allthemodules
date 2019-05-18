<?php

namespace Drupal\badge\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Badge awarded edit forms.
 *
 * @ingroup badge
 */
class BadgeAwardedForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\badge\Entity\BadgeAwarded */
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
        drupal_set_message($this->t('Created the %label Badge awarded.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Badge awarded.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.badge_awarded.canonical', ['badge_awarded' => $entity->id()]);
  }

}
