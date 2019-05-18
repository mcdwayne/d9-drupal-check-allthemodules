<?php

namespace Drupal\image_moderate\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the image_moderate entity edit forms.
 *
 * @ingroup image_moderate
 */
class ImageModerateForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\image_moderate\Entity\ImageModerate */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['status'] = [
      '#title' => $this->t('Status'),
      '#type' => 'select',
      '#default_value' => $entity->status->value,
      '#options' => [
        0 => t('Needs Review'),
        1 => t('Reviewed, can be published'),
        2 => t('Can not be published'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The status has been updated.'));
    }
    else {
      drupal_set_message($this->t('The status has been added.'));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }

}
