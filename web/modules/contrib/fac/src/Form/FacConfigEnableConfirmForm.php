<?php

namespace Drupal\fac\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirm form for disabling an index.
 */
class FacConfigEnableConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to enable the Fast Autocomplete configuration %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This enables the Fast Autocomplete configuration.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.fac_config.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Enable');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\fac\FacConfigInterface $entity */
    $entity = $this->entity;

    $entity->setStatus(TRUE)->save();

    $this->messenger()->addStatus($this->t('The Fast Autocomplete configuration %name has been enabled.', [
      '%name' => $entity->label(),
    ]));
    $form_state->setRedirect('entity.fac_config.collection');
  }

}
