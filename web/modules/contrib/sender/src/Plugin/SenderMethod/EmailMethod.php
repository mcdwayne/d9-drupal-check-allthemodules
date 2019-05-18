<?php

namespace Drupal\sender\Plugin\SenderMethod;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\sender\Entity\MessageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A method to send emails.
 *
 * @SenderMethod(id = "sender_email")
 */
class EmailMethod extends SenderMethodBase {

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              MailManagerInterface $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function send(array $data, AccountInterface $recipient, MessageInterface $message) {
    // Builds the recipient in the format "Display Name <email address>".
    $to = $recipient->getDisplayName() . ' <' . $recipient->getEmail() . '>';

    // Uses the user's preferred language.
    $langcode = $recipient->getPreferredLangcode();

    // Builds an array of parameters to build the email in hook_mail().
    $params['subject'] = $data['subject'];
    $params['body'] = $data['rendered'];
    $params['entity'] = $message;

    // Sends the email.
    $this->mailManager->mail('sender', 'sender_email', $to, $langcode, $params);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    $mail_manager = $container->get('plugin.manager.mail');
    return new static($configuration, $plugin_id, $plugin_definition, $mail_manager);
  }

}
