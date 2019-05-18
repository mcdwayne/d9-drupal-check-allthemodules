<?php

namespace Drupal\discussions\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the Discussion Type entity delete form.
 *
 * @ingroup discussions
 */
class DiscussionTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.discussion_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    \Drupal::service('router.builder')->setRebuildNeeded();

    drupal_set_message($this->t('Discussion Type "%label" has been deleted.', ['%label' => $this->entity->label()]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
