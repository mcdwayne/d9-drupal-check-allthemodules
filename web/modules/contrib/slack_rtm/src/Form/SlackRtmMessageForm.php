<?php

namespace Drupal\slack_rtm\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Slack RTM Message edit forms.
 *
 * @ingroup slack_rtm
 */
class SlackRtmMessageForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\slack_rtm\Entity\SlackRtmMessage */
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
        drupal_set_message($this->t('Created the %label Slack RTM Message.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Slack RTM Message.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.slack_rtm_message.canonical', ['slack_rtm_message' => $entity->id()]);
  }

}
