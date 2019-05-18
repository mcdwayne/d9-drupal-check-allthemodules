<?php

namespace Drupal\just_smtp\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\Plugin\Mail;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Component\Utility\Unicode;
use Drupal\encrypt\Entity\EncryptionProfile;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If PHPMailer isn't installed with Composer, then load from
// '/libraries/PHPMailer'.
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
  include_once DRUPAL_ROOT . '/libraries/PHPMailer/src/PHPMailer.php';
  include_once DRUPAL_ROOT . '/libraries/PHPMailer/src/SMTP.php';
  include_once DRUPAL_ROOT . '/libraries/PHPMailer/src/Exception.php';
}

/**
 * Modify the drupal mail system to use smtp when sending emails.
 *
 * Include the option to choose between plain text or HTML.
 *
 * @Mail(
 *   id = "JustSMTPMailSystem",
 *   label = @Translation("Just SMTP Mailer"),
 *   description = @Translation("Sends the message, using Just SMTP.")
 * )
 */
class JustSmtpPhpMailer extends PHPMailer implements MailInterface {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->config = \Drupal::config('just_smtp.settings');
  }

  /**
   * Concatenate and wrap the e-mail body for either plain-text or HTML emails.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    return $this->defaultMailSystem()->format($message);
  }

  /**
   * Send the e-mail message.
   *
   * @see drupal_mail()
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return bool
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   */
  public function mail(array $message) {
    if (!$this->config->get('just_smtp_on')) {
      return $this->defaultMailSystem()->mail($message);
    }

    $this->isSMTP();
    $this->Host = ($this->config->get('just_smtp_host') ? $this->config->get('just_smtp_host') : 'localhost');
    $this->Port = ($this->config->get('just_smtp_port') ? $this->config->get('just_smtp_port') : 25);
    if (($this->config->get('just_smtp_protocol') ? $this->config->get('just_smtp_protocol') : 'auto') == 'ssl') {
      $this->SMTPSecure = 'ssl';
    }
    if ($this->Port == 465) {
      $this->SMTPSecure = 'ssl';
      $this->Username = ($this->config->get('just_smtp_username') ? $this->config->get('just_smtp_username') : '');
    }
    if ($this->config->get('just_smtp_encrypt')) {
      $encrypt_profile = ($this->config->get('just_smtp_encrypt_profile') ? $this->config->get('just_smtp_encrypt_profile') : '');
      $password = ($this->config->get('just_smtp_password') ? $this->config->get('just_smtp_password') : '');
      $encryption_profile = EncryptionProfile::load($encrypt_profile);

      $this->Password = \Drupal::service('encryption')->decrypt($password, $encryption_profile);
    }
    else {
      $this->Password = ($this->config->get('just_smtp_password') ? $this->config->get('just_smtp_password') : '');
    }
    $this->SMTPAuth = empty($this->Username) ? FALSE : TRUE;

    $addresses = $this->addressesToArray($message['to']);
    foreach ($addresses as $address) {
      $to = $this->parseMail($address);
      if (!\Drupal::service('email.validator')->isValid($to['mail'])) {
        drupal_set_message(t('The submitted to address (@to) is not valid.', array('@to' => $address)), 'error');
        \Drupal::logger('just_smtp')->alert('The submitted to address (@to) is not valid.', [
          '@to' => $address,
        ]);
        return FALSE;
      }
      $this->addAddress($to['mail'], $to['name']);
    }

    if (isset($message['headers']['Cc'])) {
      $addresses = $this->addressesToArray($message['headers']['Cc']);
      foreach ($addresses as $address) {
        $cc = $this->parseMail($address);
        if (!\Drupal::service('email.validator')->isValid($cc['mail'])) {
          drupal_set_message(t('The submitted cc address (@cc) is not valid.', array('@cc' => $address)), 'error');
          \Drupal::logger('just_smtp')->alert('The submitted to address (@cc) is not valid.', [
            '@cc' => $address,
          ]);
          return FALSE;
        }
        $this->addCC($cc['mail'], $cc['name']);
      }
    }

    if (isset($message['headers']['Bcc'])) {
      $addresses = $this->addressesToArray($message['headers']['Bcc']);
      foreach ($addresses as $address) {
        $bcc = $this->parseMail($address);

        if (!\Drupal::service('email.validator')->isValid($bcc['mail'])) {
          drupal_set_message(t('The submitted bcc address (@bcc) is not valid.', array('@bcc' => $address)), 'error');
          \Drupal::logger('just_smtp')->alert('The submitted to address (@bcc) is not valid.', [
            '@bcc' => $address,
          ]);
          return FALSE;
        }
        $this->addBCC($bcc['mail'], $bcc['name']);
      }
      unset($message['headers']['Bcc']);
    }

    $from = $this->parseMail($message['from']);
    if (!\Drupal::service('email.validator')->isValid($from['mail'])) {
      drupal_set_message(t('The submitted from address (@from) is not valid.', array('@from' => $from['mail'])), 'error');
      \Drupal::logger('just_smtp')->alert('The submitted to address (@from) is not valid.', [
        '@from' => $from['mail'],
      ]);
      return FALSE;
    }
    $this->setFrom($from['mail'], $from['name']);

    $header = $this->mimeHeaders($message);
    $body = $message['params']['body'];

    try {
      $result = parent::smtpSend($header, $body);
    }
    catch (Exception $e) {
      $error_message = $e->getMessage();
      $message = [
        '@from' => $from['mail'],
        '@to' => $to['mail'],
        '@error_message' => $error_message,
      ];
      drupal_set_message(t('Error sending e-mail from @from to @to : @error_message', $message), 'error');
      \Drupal::logger('just_smtp')->alert('Error sending e-mail from @from to @to : @error_message', [
        '@from' => $from['mail'],
        '@to' => $to['mail'],
        '@error_message' => $error_message,
      ]);
      $result = FALSE;
    }

    return $result;
  }

  /**
   * Prepare mime headers.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return string
   *   A string containing mail headers.
   */
  protected function mimeHeaders(array $message) {
    $mimeheaders = array();
    $mimeheaders[] = 'To: ' . Unicode::mimeHeaderEncode($message['to']);
    $mimeheaders[] = 'Subject: ' . Unicode::mimeHeaderEncode($message['subject']);
    foreach ($message['headers'] as $name => $value) {
      $mimeheaders[] = $name . ': ' . Unicode::mimeHeaderEncode($value);
    }
    $mail_headers = implode("\n", $mimeheaders);
    $mail_headers .= "\n";
    return $mail_headers;
  }

  /**
   * Parse mail address like: My Name <name@example.com>.
   */
  protected function parseMail($mail) {
    if (preg_match('/^"?.*"?\s*<.*>$/', $mail)) {
      // Extract just the name.
      $name = preg_replace('/"?([^("\t\n)]*)"?.*$/', '$1', $mail);
      // Extract just the mail address.
      $email = preg_replace("/(.*)\<(.*)\>/i", '$2', $mail);
    }
    else {
      $email = $mail;
      $name = '';
    }
    return array('mail' => $email, 'name' => $name);
  }

  /**
   * Converts a comma-separated string of addresses into an array.
   */
  protected function addressesToArray($string) {
    $return = explode(',', $string);
    foreach ($return as $id => $address) {
      $return[$id] = trim($address);
    }
    return $return;
  }

  /**
   * Return the default mail system.
   *
   * @return object
   *   A PhpMail object.
   */
  protected function defaultMailSystem() {
    $default = &drupal_static(__FUNCTION__, array());
    if (!$default) {
      $default = new PhpMail();
    }
    return $default;
  }

}
