<?php

namespace Drupal\crm_core_user_sync\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the relation entity edit forms.
 */
class RelationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $individual_id = $this->getRequest()->query->get('individual_id');
    $user_id = $this->getRequest()->query->get('user_id');
    if ($individual_id || $user_id) {
      $relation = $this->getEntity();
      $relation->setIndividualId($individual_id);
      $relation->setUserId($user_id);
      $this->setEntity($relation);
    }

    $form = parent::form($form, $form_state);

    if ($individual_id) {
      $form['individual_id']['widget']['#disabled'] = TRUE;
    }

    if ($user_id) {
      $form['user_id']['widget']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toString();

    $logger_arguments = ['link' => $link];

    if ($result == SAVED_NEW) {
      $this->messenger()->addMessage($this->t('New relation has been created.'));
      $this->logger('crm_core_user_sync')->notice('Created new relation', $logger_arguments);
    }
    else {
      $this->messenger()->addMessage($this->t('The relation has been updated.'));
      $this->logger('crm_core_user_sync')->notice('Relation updated', $logger_arguments);
    }

    $form_state->setRedirect('entity.crm_core_user_sync_relation.collection');
  }

}
