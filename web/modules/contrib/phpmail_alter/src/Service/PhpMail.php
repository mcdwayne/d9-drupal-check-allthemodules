<?php

namespace Drupal\phpmail_alter\Service;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Site\Settings;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Mail backend, using PHP's native mail() function.
 */
class PhpMail implements PhpMailInterface {

  /**
   * The DebugService.
   *
   * @var \Drupal\phpmail_alter\Service\DebugServiceInterface
   */
  protected $debug;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The ModuleHandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new DebugService object.
   *
   * @param \Drupal\phpmail_alter\Service\DebugServiceInterface $debug
   *   The debug.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    DebugServiceInterface $debug,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler
    ) {
    $this->debugService = $debug;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Concatenates and wraps the email body for plain-text mails.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    $body = $message['body'];

    // Join the body array into one string.
    $body = implode("\n\n", $body);
    if (substr($message['headers']['Content-Type'], 0, 9) != "text/html") {
      // Convert any HTML to plain-text.
      $body = MailFormatHelper::htmlToText($body);
      // Wrap the mail body for sending.
      $body = MailFormatHelper::wrapMail($body);
    }
    // Note: email uses CRLF for line-endings. PHP's API requires LF on Unix.
    $line_endings = Settings::get('mail_line_endings', PHP_EOL);
    $body = preg_replace('@\r?\n@', $line_endings, $body);

    return $body;
  }

  /**
   * Sends an email message.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return bool
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   *
   * @see http://php.net/manual/function.mail.php
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   */
  public function mail(array $message) {
    // If 'Return-Path' isn't already set in php.ini, we pass it separately
    // as an additional parameter instead of in the header.
    if (isset($message['headers']['Return-Path'])) {
      $return_path_set = strpos(ini_get('sendmail_path'), ' -f');
      if (!$return_path_set) {
        $message['Return-Path'] = $message['headers']['Return-Path'];
        unset($message['headers']['Return-Path']);
      }
    }
    $mimeheaders = [];
    foreach ($message['headers'] as $name => $value) {
      if ($name == 'Content-Type' && $value == 'text/html') {
        $value = 'text/html; charset=utf-8';
      }
      if ($name == "From") {
        $this->moduleHandler->alter('phpmail_alter_from', $value);
        $mimeheaders[] = "${name}: ${value}";
      }
      else {
        $mimeheaders[] = "${name}: " . Unicode::mimeHeaderEncode($value);
      }
    }
    // Prepare mail commands.
    $subject = $message['subject'];
    if (is_object($subject)) {
      $subject = $message['subject']->__toString();
    }
    $mail_subject = Unicode::mimeHeaderEncode($subject);
    $mail_body = $this->format($message);

    // For headers, PHP's API suggests that we use CRLF normally,
    // but some MTAs incorrectly replace LF with CRLF. See #234403.
    $mail_headers = implode("\n", $mimeheaders);
    $additional_headers = '';
    if (isset($message['Return-Path']) && $this->isShellSafe($message['Return-Path'])) {
      $additional_headers = "-f {$message['Return-Path']}";
    }
    $mail_result = @mail(
      $message['to'],
      $mail_subject,
      $mail_body,
      $mail_headers,
      $additional_headers
    );
    if (!$mail_result) {
      $error = t('Unable to send email. Contact the site administrator if the problem persists.');
      drupal_set_message($error, 'error');
      $this->debugService->log($message, $mail_headers);
    }
    $this->debugService->debug($message, $mail_headers, $mail_subject, $mail_body, $additional_headers);
    return $mail_result;
  }

  /**
   * Disallows potentially unsafe shell characters.
   *
   * Functionally similar to PHPMailer::isShellSafe() which resulted from
   * CVE-2016-10045. Note that escapeshellarg and escapeshellcmd are inadequate
   * for this purpose.
   *
   * @param string $string
   *   The string to be validated.
   *
   * @return bool
   *   True if the string is shell-safe.
   *
   * @see https://github.com/PHPMailer/PHPMailer/issues/924
   * @see https://github.com/PHPMailer/PHPMailer/blob/v5.2.21/class.phpmailer.php#L1430
   *
   * @todo Rename to ::isShellSafe() and/or discuss whether this is the correct
   *   location for this helper.
   */
  protected function isShellSafe($string) {
    if (escapeshellcmd($string) !== $string || !in_array(escapeshellarg($string), ["'$string'", "\"$string\""])) {
      return FALSE;
    }
    if (preg_match('/[^a-zA-Z0-9@_\-.]/', $string) !== 0) {
      return FALSE;
    }
    return TRUE;
  }

}
