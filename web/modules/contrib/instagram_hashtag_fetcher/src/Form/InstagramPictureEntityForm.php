<?php

namespace Drupal\instagram_hashtag_fetcher\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Instagram Picture Entity edit forms.
 *
 * @ingroup instagram_pictures
 */
class InstagramPictureEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\instagram_hashtag_fetcher\Entity\InstagramPictureEntity */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Instagram Picture Entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Instagram Picture Entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.instagram_picture_entity.canonical', ['instagram_picture_entity' => $entity->id()]);
  }

}
