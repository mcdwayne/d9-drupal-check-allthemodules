<?php

namespace Drupal\mail\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\mail\MailMessageInterface;

/**
 * Defines an interface for Mail message processor plugins.
 */
interface MailMessageProcessorInterface extends PluginInspectionInterface {

  /**
   * Processes an email message entity.
   *
   * Changes should be made to the entity's subject and body directly, but it
   * should not be saved.
   *
   * @param \Drupal\mail\MailMessageInterface $entity
   *   The message entity that is being sent.
   * @param $to
   *   The email address or addresses where the message will be sent to.
   * @param $params = []
   *   (optional) Parameters to build the email.
   * @param $reply = NULL
   *   Optional email address to be used to answer.
   */
  public function processMessage(MailMessageInterface $entity, $to, $params = [], $reply = NULL);

  /**
   * Provides help for using the plugin while editing a mail message.
   *
   * @return
   *  A render array. This will be shown below the body form elements on the
   *  edit form for mail messages using this plugin.
   */
  public function getHelp();

}
