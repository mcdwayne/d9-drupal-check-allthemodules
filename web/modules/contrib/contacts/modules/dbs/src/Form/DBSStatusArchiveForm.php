<?php

namespace Drupal\contacts_dbs\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for archiving dbs statuses.
 */
class DBSStatusArchiveForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to archive %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Archive');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\contacts_dbs\Entity\DBSStatusInterface $status */
    $status = $this->entity;
    $status->setNewRevision();
    $status->archive();
    $status->save();

    $this->messenger()->addMessage($this->t('%label has been archived.', [
      '%label' => $status->label(),
    ]));
  }

}
