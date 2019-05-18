<?php

namespace Drupal\scheduled_message\Plugin\ScheduledMessage;

use Drupal\Core\Form\FormStateInterface;
use Drupal\scheduled_message\Plugin\ScheduledMessageBase;
use Drupal\scheduled_message\Plugin\ScheduledMessageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * A plugin that sets a schedule for sending a message template.
 *
 * @ScheduledMessage(
 *   id = "scheduled_email",
 *   label = @Translation("Scheduled Email"),
 *   description = @Translation("Creates a message entity with a send date specified in this plugin, and sends it on that date.")
 * )
 */
class ScheduledEmail extends ScheduledMessageBase implements ScheduledMessageInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
