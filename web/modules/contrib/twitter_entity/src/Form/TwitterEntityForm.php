<?php

namespace Drupal\twitter_entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Twitter entity edit forms.
 *
 * @ingroup twitter_entity
 */
class TwitterEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\twitter_entity\Entity\TwitterEntity */
    $form = parent::buildForm($form, $form_state);

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
        drupal_set_message($this->t('Created the %label Twitter entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Twitter entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.twitter_entity.canonical', ['twitter_entity' => $entity->id()]);
  }

}
