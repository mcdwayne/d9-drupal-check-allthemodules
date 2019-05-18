<?php

/**
 * @file
 * Contains \Drupal\cronpub\Entity\Form\CronpubEntityDeleteForm.
 */

namespace Drupal\cronpub\Entity\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\cronpub\Entity\CronpubEntity;

/**
 * Provides a form for deleting Cronpub Task entities.
 *
 * @ingroup cronpub
 */
class CronpubEntityDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $title = ($this->entity instanceof CronpubEntity && $this->entity->getTargetEntity()->label())
      ? $this->entity->getTargetEntity()->label()
      : $this->entity->label();

    return $this->t('
    Are you sure you want to delete the chronology of %name?
    (You will just delete the chronology temporarily. If you save
    your target entity again, the chronology will be recreated, if the
    cronpub date field is not empty.)', array('%name' => $title));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.cronpub_entity.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete chronology');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message(
      $this->t('content @type: deleted @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label()
        ]
        )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
