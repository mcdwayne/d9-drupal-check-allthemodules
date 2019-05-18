<?php

/**
 * @file
 * Contains \Drupal\email_verify\EmailVerifyManager.
 */

namespace Drupal\email_verify;

use Egulias\EmailValidator\EmailValidator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Utility\Unicode;
use Drupal\user\Entity\User;

/**
 * Defines an email verify manager.
 */
class EmailVerifyManager {

  /**
   * The socket connection pointer.
   */
  protected $connection;

  /**
   * An array of errors encountered when testing an email or domain.
   */
  protected $errors = array();

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a new EmailVerifyManager.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object to use.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $config_factory, EmailValidator $email_validator) {
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public function checkEmail($email) {

    // Run a quick check to determine if the email appears valid.
    if (!$this->emailValidator->isValid($email)) {
      $this->setError(t('Invalid email: @email.', array('@email' => $email)));
      return;
    }

    $host = Unicode::substr(strstr($email, '@'), 1);
    $this->connect($host);

    $mail_config = $this->configFactory->get('system.site');
    // Get the custom site notification email to use as the from email address
    // if it has been set.
    $site_mail = $mail_config->get('mail_notification');
    // If the custom site notification email has not been set, we use the site
    // default for this.
    if (empty($site_mail)) {
      $site_mail = $mail_config->get('mail');
    }
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    // Extract the <...> part, if there is one.
    if (preg_match('/\<(.*)\>/', $from, $match) > 0) {
      $from = $match[1];
    }

    // Should be good enough for RFC compliant SMTP servers.
    $request = $this->requestStack->getCurrentRequest();
    $localhost = $request->getHost();
    if (!$localhost) {
      $localhost = 'localhost';
    }

    fwrite($this->connection, "HELO $localhost\r\n");
    fgets($this->connection, 1024);
    fwrite($this->connection, "MAIL FROM: <$from>\r\n");
    $from = fgets($this->connection, 1024);
    fwrite($this->connection, "RCPT TO: <{$email}>\r\n");
    $to = fgets($this->connection, 1024);
    fwrite($this->connection, "QUIT\r\n");
    fclose($this->connection);

    if (!preg_match("/^250/", $from)) {
      // Something went wrong before we could really test the address.
      // Be on the safe side and accept it.
      \Drupal::logger('email_verify')->warning('Could not verify email address at host @host: @from', array('@host' => $host, '@from' => $from));
      $this->setError(t('Could not verify email address at host @host: @from.', array('@host' => $host, '@from' => $from)));
      return;
    }

    // This server does not like us (noos.fr behaves like this for instance).
    // Any 4xx error also means we couldn't really check except 450, which is
    // explcitely a non-existing mailbox: 450 = "Requested mail action not
    // taken: mailbox unavailable".
    if (preg_match("/(Client host|Helo command) rejected/", $to) ||
      preg_match("/^4/", $to) && !preg_match("/^450/", $to)) {
      // In those cases, accept the email, but log a warning.
      \Drupal::logger('email_verify')->warning('Could not verify email address at host @host: @to', array('@host' => $host, '@to' => $to));
      $this->setError(t('Could not verify email address at host @host: @to.', array('@host' => $host, '@to' => $to)));
      return;
    }

    if (!preg_match("/^250/", $to)) {
      \Drupal::logger('email_verify')->warning('Rejected email address: @mail. Reason: @to', array('@mail' => $email, '@to' => $to));
      $this->setError(t('%mail is not a valid email address. Please check the spelling and try again or contact us for clarification.', array('%mail' => "$email")));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkHost($host) {

    $config = $this->configFactory->get('email_verify.settings');
    if ($config->get('add_dot')) {
      $host = $host . '.';
    }

    if ($config->get('checkdnsrr')) {
      if (!checkdnsrr($host, 'ANY')) {
        $this->setError(t('@host is not a valid email host. Please check the spelling and try again.', array('@host' => "$host")));
      }
    }

    if ($config->get('gethostbyname')) {
      if (gethostbyname($host) == $host) {
        \Drupal::logger('email_verify')->warning('No IPv4 address was found using gethostbyname with @host',
          array('@host' => $host));
      $this->setError(t('@host is not a valid email host. Please check the spelling and try again or contact us for clarification.',
          array('@host' => $host)));
      }
    }

    if (!$this->connect($host)) {
      $this->setError(t('@host is not a valid email host. Please check the spelling and try again or contact us for clarification.',
          array('@host' => $host)));
    }
  }

  /**
   * {@inheritdoc}
   */
  private function connect($host) {

    if ($this->connection) {
      return true;
    }

    // Find the MX records for the host. When there are no MX records, the host
    // itself should be used.
    $mx_hosts = array();
    if (!getmxrr($host, $mx_hosts)) {
      $mx_hosts[] = $host;
    }

    $config = $this->configFactory->get('email_verify.settings');
    $timeout = $config->get('timeout');
    // Try to connect to each MX host using SMTP port 25 in turn.
    foreach ($mx_hosts as $smtp) {
      $this->connection = @fsockopen($smtp, 25, $errno, $errstr, $timeout);

      // Try each MX host sequentially if there is no response.
      if (!$this->connection) {
        continue;
      }
      // Successful SMTP connections break out of the loop.
      if (preg_match("/^220/", $out = fgets($this->connection, 1024))) {
        return true;
      }
      else {
        // The SMTP server is probably a dynamic or residential IP. Since a
        // valid domain has been used, accept the address.
        \Drupal::logger('email_verify')->warning('Could not verify email address at host @host: @out',
          array('@host' => $host, '@out' => $out));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setError($error) {
    $this->errors[] = $error;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    return $this->errors;
  }

}
