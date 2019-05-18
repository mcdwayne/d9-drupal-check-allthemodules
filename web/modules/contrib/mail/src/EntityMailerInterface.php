<?php

namespace Drupal\mail;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\TranslationManager;

/**
 * Sends email messages using an entity for the message.
 */
interface EntityMailerInterface {

  /**
   * Sends an email message entity.
   *
   * @param \Drupal\mail\MailMessageInterface $entity
   *   The message entity to send. Any entity type may be used, provided it
   *   implements the interface.
   * @param $to
   *   The email address or addresses where the message will be sent to.
   *   (TODO: more docs)
   * @param $params = []
   *   (optional) Parameters to build the email.
   * @param $reply = NULL
   *   Optional email address to be used to answer.
   */
  public function mail(MailMessageInterface $entity, $to, $params = [], $reply = NULL);

  /**
   * Process a mail message with its mail message processor plugin.
   *
   * This will typically replace tokens, and so on.
   * (This is the equivalent of hook_mail() processing the message array.)
   *
   * @see mail() for the parameters.
   */
  public function processMailMessage(MailMessageInterface $entity, $to, $params = [], $reply = NULL);

}
