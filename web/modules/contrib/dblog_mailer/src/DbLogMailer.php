<?php

namespace Drupal\dblog_mailer;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Logs events in the watchdog database table.
 */
class DbLogMailer {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * The config object for the dblog_connections settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig $config
   */
  protected $config;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManager $mailManager
   */
  protected $mailManager;

  /**
   * Enable the mailing functionality.
   *
   * @var Boolean $enableEmails
   */
  protected $enableEmails;

  /**
   * The maximum number of logs row to process in 1 run.
   *
   * @var Int $rowLimit;
   */
  protected $rowLimit;

  /**
   * The reply-to email address to use.
   *
   * @var $replyTo;
   */
  protected $replyTo;

  /**
   * The log entries to work on.
   *
   * @var array $logs
   */
  protected $logs;

  /**
   * The emails to send.
   *
   * @var array $emails
   */
  protected $emails;

  /**
   * The channels to listen.
   *
   * @var array $channels
   */
  protected $channels;

  /**
   * Constructs a DbLogMailer object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   */
  //ContainerInterface $container
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager) {
    $this->database     = $database;
    $this->mailManager  = $mail_manager;
    $this->config       = $config_factory->get('dblog_mailer.settings');
    $this->rowLimit     = $this->config->get('default_row_limit');
    $this->enableEmails = $this->config->get('enable');
    $this->replyTo      = !empty($this->config->get('reply_to')) ? $this->config->get('reply_to') : null;
    $this->logs         = [];
    $this->emails       = [];
    $this->channels     = [];
    $this->initializeEmailsAndChannels();
  }

  /*
   * Initialize the emails and channels properties from configuration.
   * */
  private function initializeEmailsAndChannels() {
    $emails_list = explode("\r\n", Unicode::strtolower($this->config->get('emails_list')));
    if (!empty($emails_list)) {
      foreach ($emails_list as $emails_list_item) {
        $email_row = explode('|', $emails_list_item);
        if (!isset($email_row) || (count($email_row) != 3)) {
          \Drupal::logger('dbloger_mail')->error("DBLog email configuration is not correct");
          //throw new Exception("DBLog email configuration is not correct");
        } else {
          $email_item = [];
          $email_item['channel'] = $email_row[0];
          $email_item['subject'] = $email_row[1];
          $email_item['recipients'] = $email_row[2];
          $this->emails[] = $email_item;
          $this->channels[] = $email_row[0];
        }
      }
    }
  }

  /*
   * Compile available log messages for one specific log channel.
   *
   * @param string $channel
   *    The name of the channel for which to compile log messages.
   *
   * @return string
   *    The concatenated log messages for the given channel.
   * */
  private function compileLogMessages($channel) {
    $message = "";
    foreach ($this->logs as $log) {
      if ($log->type == $channel) {
        $message .= $log->message . "\r\n\r\n";
      }
    }
    return $message;
  }

  /*
   * Build the recipient list and validate email addresses.
   *
   * @param string $recipients
   *    The recipients list from the module configuration, formated as
   *    email1@domain.com;email2@domain.com
   *
   * @return string
   *    - return a string containing email addresses formated as
   *    email1@domain.com, email2@domain.com
   *    - returns null if one of the email addresses does not validate
   * */
  private function buildReceipientsList($recipients) {
    $recipients_list = [];
    $recipients_list = explode(";", $recipients);
    foreach ($recipients_list as $recipient) {
      // As we are here, let's validate the recipients email addresses
      if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        \Drupal::logger('dblog_mailer')->error("Invalid recipient address: @recipient", [
          "@recipient" => $recipient
        ]);
        return null;
      }
    }
    return implode(", ", $recipients_list);
  }

  /*
   * Send 1 email.
   *
   * @param array $email
   *    The email settings as defined in the module setting form.
   *
   * @return array
   *    - returns null if any error occurs
   *    - returns the result of the MailManager::doMail() method otherwise.
   * */
  private function sendEmail(array $email) {
    if (!empty($email['channel']) && !empty($email['subject']) && !empty($email['recipients'])) {
      $channel = $email['channel'];
      if (!($recipients = $this->buildReceipientsList($email['recipients']))) {
        return null;
      }
      $emails_params = [
        "body" => $this->compileLogMessages($channel),
        "subject" => $email['subject']
      ];

      // call doMail() from mailManager - this will then call dblog_mailer_mail() hook
      return $this->mailManager->doMail('dblog_mailer', 'log_email', $recipients,'en', $emails_params, $this->replyTo);

    } else {
      \Drupal::logger('dblog_mailer')->error("Cannot send emails with missing parameters.");
      return null;
    }
  }

  /*
   * Log that an individual email was sent in watchdog_mailer table
   *
   * @param array $email
   *    The email settings as defined in the module setting form.
   *
   * @param string $delivery_status
   *    One of the delivery status constants defined in dblog_mailer.module
   * */
  private function flagLogsAsProcessed(array $email, $delivery_status) {
    $channel = $email['channel'];

    $insert = $this->database->insert('watchdog_mailer')
      ->fields(['wid', 'subject', 'recipients', 'message', 'delivery_status']);

    foreach ($this->logs as $log) {
      if ($log->type == $channel) {

        $insert->values([
          'wid' => $log->wid,
          'subject' => $email['subject'],
          'recipients' => $email['recipients'],
          'message' => $log->message,
          'delivery_status' => $delivery_status
        ]);
      }
    }
    $insert->execute();
  }

  /*
   * Fetch the logs to be processed from the database
   *
   * @return boolean
   *    Return true if some logs where feched, false otherwise.
   * */
  public function fetchLogs() {
    //@TODO: implement SEVERITY as a module setting
    $severity = '4';

    $query = $this->database->select('watchdog', 'w');
    $query->fields('w', ['wid', 'type', 'message', 'severity']);
    $query->orderBy('w.wid', 'ASC');
    $query->condition('w.type', $this->channels, "IN");
    $query->condition('w.severity', $severity, ">=");
    $query->leftJoin('watchdog_mailer', 'wm', 'w.wid = wm.wid');
    $query->isNull('wm.wid');

    if ($this->rowLimit > 0) {
      $query->range(0, $this->rowLimit);
    }
    $this->logs = $query->execute()->fetchAll();

    return is_array($this->logs) && !empty($this->logs);
  }

  /*
   * Process the logs and send emails
   * */
  public function processLogTable() {
    foreach ($this->emails as $email) {
      if (!$this->enableEmails) {
        $this->flagLogsAsProcessed($email, DBLOG_MAILER_DELIVERY_SKIPPED);
      } elseif ($email_status = $this->sendEmail($email)) {
        if (isset($email_status['result']) && ($email_status['result'] == true)) {
          $this->flagLogsAsProcessed($email, DBLOG_MAILER_DELIVERY_SUCCESS);
        } else {
          $this->flagLogsAsProcessed($email, DBLOG_MAILER_DELIVERY_FAILURE);
        }
      } else {
        $this->flagLogsAsProcessed($email, DBLOG_MAILER_DELIVERY_FAILURE);
      }
    }
  }
}
