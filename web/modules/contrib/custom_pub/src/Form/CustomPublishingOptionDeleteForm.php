<?php

/**
 * @file
 * Contains \Drupal\custom_pub\Form\CustomPublishingOptionDeleteForm.
 */

namespace Drupal\custom_pub\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Custom publishing option entities.
 */
class CustomPublishingOptionDeleteForm extends EntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.custom_publishing_option.collection');
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

    drupal_set_message(
      $this->t('Custom publishing option "%label" has been deleted.',
        [
          '@type' => $this->entity->bundle(),
          '%label' => $this->entity->label()
        ]
      )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
