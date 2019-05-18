<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Cloudwords translatable edit forms.
 *
 * @ingroup cloudwords
 */
class CloudwordsTranslatableForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\cloudwords\Entity\CloudwordsTranslatable */
    $form = parent::buildForm($form, $form_state);
//    $entity = $this->entity;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Cloudwords translatable.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Cloudwords translatable.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.cloudwords_translatable.canonical', ['cloudwords_translatable' => $entity->id()]);
  }

}
