<?php

namespace Drupal\swiper\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Swiper options entities.
 */
class SwiperDeleteForm extends EntityConfirmFormBase {

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
    return new Url('entity.swiper.collection');
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
      $this->t('Deleted the %label Swiper options.',
        [
          '%label' => $this->entity->label(),
        ]
        )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if ($this->entity->id() == 'default') {
      $form['#title'] = $this->t('The default options cannot be deleted.');
      $form['description'] = ['#markup' => t('Please click Cancel to go back to the list of options.')];
      $form['actions']['submit']['#access'] = FALSE;
    }

    return $form;
  }

}
