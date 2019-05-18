<?php

namespace Drupal\parallax_bg\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Parallax element entities.
 */
class ParallaxElementDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\parallax_bg\Entity\ParallaxElementInterface $entity */
    $entity = $this->entity;

    return $this->t('Are you sure you want to delete %name?', [
      '%name' => $entity->getSelector(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.parallax_element.collection');
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
    /** @var \Drupal\parallax_bg\Entity\ParallaxElementInterface $entity */
    $entity = $this->entity;
    $entity->delete();

    drupal_set_message(
      $this->t('content @type: deleted @label.',
        [
          '@type'  => $entity->bundle(),
          '@label' => $entity->getSelector(),
        ]
      )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
