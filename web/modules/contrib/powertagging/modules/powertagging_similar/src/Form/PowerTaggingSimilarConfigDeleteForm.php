<?php

/**
 * @file
 * Contains \Drupal\powertagging_similar\Form\PowerTaggingSimilarConfigDeleteForm.
 */

namespace Drupal\powertagging_similar\Form;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\powertagging_similar\Entity\PowerTaggingSimilarConfig;

class PowerTaggingSimilarConfigDeleteForm extends EntityConfirmFormBase{
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete PowerTagging SeeAlso widget "%name"?', array('%name' => $this->entity->get('title')));
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the contact list.
   */
  public function getCancelURL() {
    return new Url('entity.powertagging_similar.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. log() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var PowerTaggingSimilarConfig $entity */
    $entity = $this->getEntity();
    $entity->delete();

    drupal_set_message(t('PowerTagging SeeAlso widget "%title" has been deleted.', array('%title' => $entity->getTitle())));
    $form_state->setRedirect('entity.powertagging.collection');
  }
}