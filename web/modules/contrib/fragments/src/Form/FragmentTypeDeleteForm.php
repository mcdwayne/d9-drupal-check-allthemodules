<?php

namespace Drupal\fragments\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete fragment type entities.
 */
class FragmentTypeDeleteForm extends EntityConfirmFormBase {

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
    return new Url('entity.fragment_type.collection');
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
    try {
      $this->entity->delete();

      $this->messenger()->addMessage(
        $this->t('Fragment type %label deleted.',
          [
            '%label' => $this->entity->label(),
          ]
        )
      );
    }
    catch (EntityStorageException $e) {
      $replacements = ['%label' => $this->entity->label()];
      $this->messenger()->addError(
        $this->t('A problem occurred trying to delete fragment type %label.',
          $replacements)
      );
      watchdog_exception(
        'fragments',
        $e,
        'A problem occurred trying to delete fragment type %label',
        $replacements);
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
