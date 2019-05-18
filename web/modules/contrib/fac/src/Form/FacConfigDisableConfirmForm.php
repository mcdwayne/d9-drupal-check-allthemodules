<?php

namespace Drupal\fac\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Defines a confirm form for disabling an index.
 */
class FacConfigDisableConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to disable the Fast Autocomplete configuration %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This disables the Fast Autocomplete configuration. The generated json files for the configuration are deleted.');
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
    return $this->t('Disable');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\fac\FacConfigInterface $entity */
    $entity = $this->entity;

    $entity->setStatus(FALSE)->save();

    // Delete the Fast Autocomplete configuration json files.
    file_unmanaged_delete_recursive(PublicStream::basePath() . '/fac-json/' . $entity->id());

    $this->messenger()->addStatus($this->t('The Fast Autocomplete configuration %name has been disabled.', [
      '%name' => $entity->label(),
    ]));
    $form_state->setRedirect('entity.fac_config.collection');
  }

}
