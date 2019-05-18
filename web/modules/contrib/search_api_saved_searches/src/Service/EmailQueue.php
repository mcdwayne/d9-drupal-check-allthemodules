<?php

namespace Drupal\search_api_saved_searches\Service;

use Drupal\Core\DestructableInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Provides functionality for sending e-mails at the end of the page request.
 */
class EmailQueue implements DestructableInterface {

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mail;

  /**
   * The queued mails, as argument arrays.
   *
   * @var array[]
   */
  protected $mails = [];

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail
   *   The mail manager service.
   */
  public function __construct(MailManagerInterface $mail) {
    $this->mail = $mail;
  }

  /**
   * Queues an e-mail to be sent.
   *
   * @param array $args
   *   The arguments that should be passed when sending the mail.
   *
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   */
  public function queueMail(array $args) {
    $this->mails[] = $args;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    foreach ($this->mails as $i => $args) {
      call_user_func_array([$this->mail, 'mail'], $args);
      unset($this->mails[$i]);
    }
  }

}
