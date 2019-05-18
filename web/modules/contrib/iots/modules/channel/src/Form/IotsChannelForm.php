<?php

namespace Drupal\iots_channel\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Iots Channel edit forms.
 *
 * @ingroup iots_channel
 */
class IotsChannelForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\iots_channel\Entity\IotsChannel */
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
        drupal_set_message($this->t('Created the %label Iots Channel.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Iots Channel.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.iots_channel.canonical', ['iots_channel' => $entity->id()]);
  }

}
