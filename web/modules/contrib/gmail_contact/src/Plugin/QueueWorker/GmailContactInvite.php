<?php

/**
 * @file
 * Contains \Drupal\gmail_contact\Plugin\Block.
 */

namespace Drupal\gmail_contact\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = "gmail_contact_invite",
 *   title = @Translation("Gmail Contact Queue"),
 *   cron = {"time" = 60}
 * )
 */
class GmailContactInvite extends QueueWorkerBase {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a new GmailContactInviteForm.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(MailManagerInterface $mail_manager) {
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.mail'));
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $email = $vars['email'];
    $from = $vars['from'];
    $params = array();

    // Send e-mail.
    $language_code = \Drupal::languageManager()->getDefaultLanguage()->getId();

    // Send the mail, and check for success. Note that this does not guarantee
    // message delivery; only that there were no PHP-related issues encountered
    // while sending.
    $result = $this->mailManager->mail('gmail_contact', 'invite', $email, $language_code, array(), $from);
    $result = drupal_mail('gmail_contact', 'invite', $email, $language, $params, $from, TRUE);
    if ($result) {
      watchdog('gmail_contact', 'Successfully send e-mail %to).', array('%to' => $email));
    }
  }

}
?>
