<?php

namespace Drupal\pusher_integration\Form;

/**
 * @file
 * Contains \Drupal\pusher_integration\Form\ChannelPathMapDeleteForm.
 */

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete a ChannelPathMap entry.
 */
class ChannelPathMapDeleteForm extends EntityConfirmFormBase {

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
    return new Url('channel_path_map.list');
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
    $this->entity->delete();
    drupal_set_message($this->t('Channel-Path-Map entry %id has been deleted.', ['%id' => $this->entity->id()]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
