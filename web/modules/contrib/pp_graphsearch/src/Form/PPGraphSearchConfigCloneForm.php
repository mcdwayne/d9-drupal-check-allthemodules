<?php

/**
 * @file
 * Contains \Drupal\pp_graphsearch\Form\PPGraphSearchConfigCloneForm.
 */

namespace Drupal\pp_graphsearch\Form;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pp_graphsearch\Entity\PPGraphSearchConfig;
use Drupal\pp_graphsearch\PPGraphSearch;

class PPGraphSearchConfigCloneForm extends EntityConfirmFormBase{
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to clone the PoolParty GraphSearch configuration "@title"?', array('@title' => $this->entity->get('title')));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '<b>ATTENTION:</b> '
      . $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelURL() {
    return new Url('entity.pp_graphsearch.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clone configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var PPGraphSearchConfig $entity */
    $entity = $this->getEntity();

    $new_entity = PPGraphSearch::createConfiguration(
      $entity->getTitle() . ' (CLONE)',
      $entity->getSearchSpaceId(),
      $entity->getConnectionId(),
      $entity->getConfig()
    );

    \Drupal::messenger()->addMessage(t('PoolParty GraphSearch configuration "%title" was successfully cloned.', array('%title' => $entity->getTitle())));
    $form_state->setRedirect('entity.pp_graphsearch.edit_config_form', array('pp_graphsearch' => $new_entity->id()));
  }
}