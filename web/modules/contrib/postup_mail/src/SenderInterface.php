<?php

namespace Drupal\postup_mail;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

interface SenderInterface {

  /**
   * Send request to PostUP server.
   *
   * @param string $type
   *  The type for api. For example 'templatemailing'.
   * @param array $options
   *  The options for request. See /Guzzle/Client::applyOptions() function.
   */
  public function send($type, $options = []);

  /**
   * Send mail with template.
   *
   * @param string $email
   *  The email address for send message.
   * @param array|bool $content
   *  The custom text for mail.
   *  $content = [
   *     'subject' => 'Test Subject',
   *     'fromName' => 'Tester',
   *     'textBody' => 'Template Mailing Body',
   *   ];
   */
  public function send_mail_template($email, $content = FALSE);

  /**
   * Add recipient.
   *
   * @param string $email
   *  The email address for recipient.
   */
  public function addRecipient($email);

  /**
   * Get list.
   *
   * @param int $listId
   *  The list id.
   */
  public function getList($listId);

  /**
   * Add recipient to list.
   *
   * @param int $listId
   *  The list id.
   * @param int $recipientId
   *  The recipient id.
   * @param string $status
   *  The string status NORMAL or UNSUB.
   */
  public function addRecipientToList($listId, $recipientId, $status = 'NORMAL');

}
