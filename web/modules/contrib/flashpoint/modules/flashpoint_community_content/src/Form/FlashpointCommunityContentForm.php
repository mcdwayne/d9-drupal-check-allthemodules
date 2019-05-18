<?php

namespace Drupal\flashpoint_community_content\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Flashpoint community content edit forms.
 *
 * @ingroup flashpoint_community_content
 */
class FlashpointCommunityContentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\flashpoint_community_content\Entity\FlashpointCommunityContent */
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
        drupal_set_message($this->t('Created the %label Flashpoint community content.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Flashpoint community content.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.flashpoint_community_content.canonical', ['flashpoint_community_content' => $entity->id()]);
  }

}
