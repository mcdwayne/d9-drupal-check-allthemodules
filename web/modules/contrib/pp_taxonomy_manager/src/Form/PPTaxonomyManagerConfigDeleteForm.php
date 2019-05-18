<?php

/**
 * @file
 * Contains \Drupal\smart_glossary\Form\PPTaxonomyManagerConfigDeleteForm.
 */

namespace Drupal\pp_taxonomy_manager\Form;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfig;

class PPTaxonomyManagerConfigDeleteForm extends EntityConfirmFormBase{
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %name?', array('%name' => $this->entity->get('title')));
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the contact list.
   */
  public function getCancelURL() {
    return new Url('entity.pp_taxonomy_manager.collection');
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
    /** @var PPTaxonomyManagerConfig $entity */
    $entity = $this->getEntity();
    $entity->delete();

    drupal_set_message(t('PoolParty Taxonomy Manager configuration "%title" has been deleted.', array('%title' => $entity->getTitle())));
    $form_state->setRedirect('entity.pp_taxonomy_manager.collection');
  }
}