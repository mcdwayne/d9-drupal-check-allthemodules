<?php

namespace Drupal\scheduled_message\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Edit form for a scheduled message plugin.
 */
class ScheduledMessageEditForm extends ScheduledMessageFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $entity_id = NULL, $scheduled_message = NULL) {
    $form = parent::buildForm($form, $form_state, $entity_type, $entity_id, $scheduled_message);

    $form['#title'] = $this->t('Edit %label', ['%label' => $this->scheduledMessage->label()]);
    $form['actions']['submit']['#value'] = $this->t('Update message');

    return $form;
  }

  /**
   * Get the message plugin based on the machine_id.
   *
   * @param string $scheduled_message
   *   The message id.
   *
   * @return \Drupal\scheduled_message\Plugin\ScheduledMessageInterface
   *   The plugin.
   */
  protected function getMessagePlugin($scheduled_message) {
    return $this->baseEntity->getMessage($scheduled_message);
  }

}
