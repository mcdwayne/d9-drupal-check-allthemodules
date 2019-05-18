<?php

namespace Drupal\scheduled_message\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin form to add a scheduled message.
 */
class ScheduledMessageAddForm extends ScheduledMessageFormBase {

  /**
   * Get the message plugin for a particular message id.
   *
   * @param string $scheduled_message
   *   The scheduled message machine id.
   *
   * @return \Drupal\scheduled_message\Plugin\ScheduledMessageInterface
   *   The message plugin.
   */
  protected function getMessagePlugin($scheduled_message) {
    /** @var \Drupal\scheduled_message\Plugin\ScheduledMessageInterface $scheduled_message */
    $scheduled_message = $this->scheduledMessageManager->createInstance($scheduled_message);
    return $scheduled_message;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $entity_id = NULL, $scheduled_message = NULL) {
    $form = parent::buildForm($form, $form_state, $entity_type, $entity_id, $scheduled_message);

    $form['#title'] = $this->t('Add %label', ['%label' => $this->scheduledMessage->label()]);
    $form['actions']['submit']['#value'] = $this->t('Add Scheduled Message');

    return $form;
  }

}
