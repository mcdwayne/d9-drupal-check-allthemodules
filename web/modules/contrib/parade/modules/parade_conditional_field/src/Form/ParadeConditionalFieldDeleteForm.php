<?php

namespace Drupal\parade_conditional_field\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Parade conditional field config entities.
 */
class ParadeConditionalFieldDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %bundle #%numeric_id condition?', [
      '%bundle' => $this->entity->getBundle(),
      '%numeric_id' => $this->entity->getNumericId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url("entity.paragraph.parade_conditional_field", ['paragraphs_type' => $this->entity->getBundle()]);
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

    drupal_set_message($this->t('Deleted the the %bundle #%numeric_id condition.', [
      '%bundle' => $this->entity->getBundle(),
      '%numeric_id' => $this->entity->getNumericId(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
