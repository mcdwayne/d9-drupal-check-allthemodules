<?php

namespace Drupal\cumulio\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Cumulio entity edit forms.
 *
 * @ingroup cumulio
 */
class CumulioEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\cumulio\Entity\CumulioEntity */
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
        drupal_set_message($this->t('Created the %label Cumulio entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Cumulio entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.cumulio_entity.canonical', ['cumulio_entity' => $entity->id()]);
    token_clear_cache();
  }

}
