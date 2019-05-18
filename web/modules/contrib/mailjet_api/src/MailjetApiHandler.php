<?php

namespace Drupal\mailjet_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;
use Mailjet\Resources;
use Mailjet\Client;
use Html2Text\Html2Text;
use Drupal\Component\Utility\EmailValidatorInterface;

/**
 * Mail handler to send out an email message array to the Mailjet API.
 */
class MailjetApiHandler implements MailjetApiHandlerInterface {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $mailjetApiConfig;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Mailjet client.
   *
   * @var \Mailjet\Client
   */
  protected $client;

  /**
   * Email validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * Constructs a new \Drupal\mailjet_api\MailjetApiHandler object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerInterface $logger, EmailValidatorInterface $email_validator) {
    $this->configFactory = $configFactory;
    $this->mailjetApiConfig = $this->configFactory->get('mailjet_api.settings');
    $this->logger = $logger;
    $this->emailValidator = $email_validator;
    $this->client = new Client(
      $this->mailjetApiConfig->get('api_key_public'),
      $this->mailjetApiConfig->get('api_key_secret'),
      TRUE,
      ['version' => 'v3.1']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail(array $body) {
    try {
      if (self::checkApiSettings() === FALSE) {
        $this->logger->error('Failed to send message from %from to %to. Please check the Mailjet API settings.',
          [
            '%from' => $body['Messages'][0]['From']['Email'],
            '%to' => $body['Messages'][0]['To'][0]['Email'],
          ]
        );
        return FALSE;
      }

      $response = $this->client->post(Resources::$Email, ['body' => $body]);
      // Debug mode: log all messages.
      if ($this->mailjetApiConfig->get('debug_mode')) {
        $this->logger->notice('Message sent from @from to @to. Status code: @status. Data: <pre>@data</pre>',
          [
            '@from' => $body['Messages'][0]['From']['Email'],
            '@to' => $body['Messages'][0]['To'][0]['Email'],
            '@status' => $response->getStatus(),
            '@data' => $response->getData(),
          ]
        );
      }
      if ($response->getStatus() == '200') {
        return TRUE;
      }
      return FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error('Exception occurred while trying to send test email from %from to %to. @code: @message.',
        [
          '%from' => $body['Messages'][0]['From']['Email'],
          '%to' => $body['Messages'][0]['To'][0]['Email'],
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]
      );
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function status($showMessage = FALSE) {
    return self::checkLibrary($showMessage) && self::checkApiSettings($showMessage);
  }

  /**
   * {@inheritdoc}
   */
  public static function checkLibrary($showMessage = FALSE) {
    $libraryStatus = class_exists('\Mailjet\Client');
    if ($showMessage === FALSE) {
      return $libraryStatus;
    }

    if ($libraryStatus === FALSE) {
      drupal_set_message(t('The Mailjet library has not been installed correctly.'), 'warning');
    }
    return $libraryStatus;
  }

  /**
   * {@inheritdoc}
   */
  public static function checkApiSettings($showMessage = FALSE) {
    $mailjetSettings = \Drupal::config('mailjet_api.settings');
    $api_key_public = $mailjetSettings->get('api_key_public');
    $api_key_secret = $mailjetSettings->get('api_key_secret');

    if (empty($api_key_public) || empty($api_key_secret)) {
      if ($showMessage) {
        drupal_set_message(t("Please check your API settings. API keys shouldn't be empty."), 'warning');
      }
      return FALSE;
    }

    if (self::validateKey($api_key_public, $api_key_secret) === FALSE) {
      if ($showMessage) {
        drupal_set_message(t("Couldn't connect to the Mailjet API. Please check your API settings."), 'warning');
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateKey($api_key_public, $api_key_secret) {
    if (self::checkLibrary() === FALSE) {
      return FALSE;
    }
    $mailjet = new Client(
      $api_key_public,
      $api_key_secret,
      TRUE,
      ['version' => 'v3.1']
    );

    try {
      $response = $mailjet->get((Resources::$Apikey));
      if ($response->getStatus() == 200) {
        return TRUE;
      }
    }
    catch (\Exception $e) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMessagesBody(array $message) {
    // Build the Mailjet message array.
    $mailjet_message = [];

    if ($this->emailValidator->isValid($message['from'])) {
      $mailjet_message['From']['Email'] = $message['from'];
    }
    elseif (isset($message['params']['from']) && $this->emailValidator->isValid($message['params']['from'])) {
      $mailjet_message['From']['Email'] = $message['params']['from'];
    }
    elseif (isset($message['params']['from_mail']) && $this->emailValidator->isValid($message['params']['from_mail'])) {
      $mailjet_message['From']['Email'] = $message['params']['from_mail'];
    }
    else {
      // Fallback to the site mail.
      $mailjet_message['From']['Email'] = $this->configFactory->get('system.site')->get('mail');
    }

    // The site name as the default sender Name.
    $mailjet_message['From']['Name'] = $this->configFactory->get('system.site')->get('name');
    // If an empty Name is pass as a parameter, we remove the sender Name form the message built.
    if (isset($message['params']['from_name'])) {
      if (!empty($message['params']['from_name'])) {
        $mailjet_message['From']['Name'] = $message['params']['from_name'];
      }
      else {
        unset($mailjet_message['From']['Name']);
      }
    }

    $tos = explode(',', $message['to']);
    foreach ($tos as $key => $to) {
      if ($this->emailValidator->isValid($to)) {
        $mailjet_message['To'][]['Email'] = $to;
      }
    }

    $mailjet_message['Subject'] = $message['subject'];

    // Manage the body part.
    if (is_array($message['body'])) {
      $body = implode("\n\n", $message['body']);
    }
    else {
      $body = $message['body'];
    }
    $mailjet_message['HTMLPart'] = $body;

    if (isset($message['plain'])) {
      $mailjet_message['TextPart'] = $message['plain'];
    }
    else {
      $converter = new Html2Text($body);
      $mailjet_message['TextPart'] = $converter->getText();
    }

    // Add the CC and BCC fields if not empty.
    if (!empty($message['params']['cc'])) {
      $ccs = explode(',', $message['params']['cc']);
      foreach ($ccs as $key => $cc) {
        if ($this->emailValidator->isValid($cc)) {
          $mailjet_message['Cc'][]['Email'] = $cc;
        }
      }
    }

    if (!empty($message['params']['bcc'])) {
      $bccs = explode(',', $message['params']['bcc']);
      foreach ($bccs as $key => $bcc) {
        if ($this->emailValidator->isValid($bcc)) {
          $mailjet_message['Bcc'][]['Email'] = $bcc;
        }
      }
    }

    // Support CC / BCC provided by webform module.
    if (!empty($message['params']['cc_mail'])) {
      $ccs = explode(',', $message['params']['cc_mail']);
      foreach ($ccs as $key => $cc) {
        if ($this->emailValidator->isValid($cc)) {
          $mailjet_message['Cc'][]['Email'] = $cc;
        }
      }
    }

    if (!empty($message['params']['bcc_mail'])) {
      $bccs = explode(',', $message['params']['bcc_mail']);
      foreach ($bccs as $key => $bcc) {
        if ($this->emailValidator->isValid($bcc)) {
          $mailjet_message['Bcc'][]['Email'] = $bcc;
        }
      }
    }

    // Add Reply-To as header according to Mailjet API.
    if (!empty($message['reply-to']) && $this->emailValidator->isValid($message['reply-to'])) {
      $mailjet_message['ReplyTo']['Email'] = $message['reply-to'];
    }
    else {
      $mailjet_message['ReplyTo']['Email'] = $mailjet_message['From']['Email'];
    }

    // Make sure the files provided in the attachments array exist.
    if (!empty($message['params']['attachments'])) {
      $attachments = [];
      foreach ($message['params']['attachments'] as $attachment) {
        if (is_array($attachment)) {
          $attachments[] = [
            'Filename' => isset($attachment['filename']) ? $attachment['filename'] : '',
            'ContentType' => isset($attachment['filemime']) ? $attachment['filemime'] : '',
            'Base64Content' => isset($attachment['filecontent']) ? base64_encode($attachment['filecontent']) : '',
          ];
        }
        elseif (file_exists($attachment)) {
          $attachments[] = [
            'Filename' => basename($attachment),
            'ContentType' => \Drupal::service('file.mime_type.guesser')->guess($attachment),
            'Base64Content' => base64_encode(file_get_contents($attachment)),
          ];
        }
      }

      if (count($attachments) > 0) {
        $mailjet_message['Attachments'] = $attachments;
      }
    }

    // Inline attachments.
    if (isset($message['params']['InlinedAttachments'])) {
      foreach ($message['params']['InlinedAttachments'] as $attachment) {
        $mailjet_message['InlinedAttachments'][] = $attachment;
      }
    }

    if ($this->mailjetApiConfig->get('custom_campaign')) {
      if (isset($message['params']['CustomCampaign']) && !empty($message['params']['CustomCampaign'])) {
        $mailjet_message['CustomCampaign'] = $message['params']['CustomCampaign'];

        // If a custom campaign is set, add the deduplicate tag if set.
        if ($this->mailjetApiConfig->get('deduplicate_campaign')) {
          if (isset($message['params']['DeduplicateCampaign']) && $message['params']['DeduplicateCampaign']) {
            $mailjet_message['DeduplicateCampaign'] = TRUE;
          }
        }
      }
    }

    // @todo Figure how to not display image inlined as attachment too ?
    // if ($this->mailjetApiConfig->get('embed_image')) {
    // $mailjet_message['Headers']['Content-Type'] = 'multipart/related';
    // }
    // Build the body for Mailjet API.
    $body = ['Messages' => [$mailjet_message]];

    // Mailjet will accept the message but will not send it.
    if ($this->mailjetApiConfig->get('sandbox_mode')) {
      $body['SandboxMode'] = TRUE;
    }

    return $body;
  }

}
