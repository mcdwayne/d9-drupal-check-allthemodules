<?php

/**
 * @file
 * Contains \Drupal\pp_graphsearch_similar\Form\PPGraphSearchSimilarConfigDeleteForm.
 */

namespace Drupal\pp_graphsearch_similar\Form;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pp_graphsearch_similar\Entity\PPGraphSearchSimilarConfig;

class PPGraphSearchSimilarConfigDeleteForm extends EntityConfirmFormBase{
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete PoolParty GraphSearch SeeAlso widget "%name"?', array('%name' => $this->entity->get('title')));
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the contact list.
   */
  public function getCancelURL() {
    return new Url('entity.pp_graphsearch_similar.collection');
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
    /** @var PPGraphSearchSimilarConfig $entity */
    $entity = $this->getEntity();
    $entity->delete();

    \Drupal::messenger()->addMessage(t('PoolParty GraphSearch SeeAlso widget "%title" has been deleted.', array('%title' => $entity->getTitle())));
    $form_state->setRedirect('entity.pp_graphsearch_similar.collection');
  }
}