<?php

namespace Drupal\pardot\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PardotCampaignDeleteForm.
 *
 * Provides a confirm form for deleting the Pardot Campaign.
 *
 * @package Drupal\pardot\Form
 *
 * @ingroup pardot
 */
class PardotCampaignDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete Pardot Campaign %label?', array(
      '%label' => $this->entity->label(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to delete Pardot Campaign %label? This action cannot be undone.', array(
      '%label' => $this->entity->label(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete Pardot Campaign');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('pardot.campaign.list');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the entity.
    $this->entity->delete();

    // Set a message that the entity was deleted.
    drupal_set_message($this->t('Pardot Campaign %label was deleted.', array(
      '%label' => $this->entity->label(),
    )));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
