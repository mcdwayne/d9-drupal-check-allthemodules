<?php
/**
 * @file
 * Contains e-Boks sender definition.
 */

namespace Drupal\eboks;

use Drupal\eboks\Entity\EboksMessage;
use Drupal\eboks\Entity\EboksMessageInterface;
use Mpdf\Mpdf;

/**
 * Class e-Boks MSOutlook sender.
 *
 * @package Drupal\eboks
 */
class EboksSenderMSOutlook implements EboksSenderInterface {

  /**
   * EboksSender configuration.
   *
   * @var array $config
   */
  protected $config;

  /**
   * Array with messages.
   *
   * @var array $messages
   */
  protected $messages;

  /**
   * Array with sender information.
   *
   * @var mixed $senderData
   */
  protected $senderData;

  /**
   * Logger interface.
   *
   * @var \Psr\Log\LoggerInterface $logger
   */
  protected $logger;

  /**
   * Receiver.
   *
   * @var string $receiver
   */
  protected $receiverId;

  /**
   * Receiver.
   *
   * @var string $receiverType
   */
  protected $receiverType;

  /**
   * Valid config flag.
   *
   * @var bool $configValid
   */
  protected $configValid = TRUE;

  /**
   * Eboks message entity.
   *
   * @var EboksMessageInterface $entity
   */
  protected $entity;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Sender construct method.
   *
   * @param string $receiver_id
   *   Receiver id.
   * @param string $receiver_type
   *   Receiver type: CPR or CVR.
   * @param array $messages
   *   Array with messages.
   * @param mixed $sender_data
   *   Sender information data.
   */
  public function __construct($receiver_id, $receiver_type, array $messages = [], $sender_data = FALSE) {
    $this->logger = \Drupal::logger('eboks');
    $this->config = \Drupal::service('config.factory')->get('eboks.msoutlook');
    $this->mailManager = \Drupal::service('plugin.manager.mail');
    $this->languageManager = \Drupal::service('language_manager');
    // Validate sender config.
    $this->validateConfig();

    $this->receiverId = $receiver_id;
    $this->receiverType = $receiver_type;
    $this->messages = $messages;
    $this->senderData = $sender_data;
  }

  /**
   * Initialization of sending process and create sending entity.
   */
  public function init() {
    if (!$this->configValid) {
      return FALSE;
    }
    $this->entity = EboksMessage::create([
      'shipment_xml' => FALSE,
      'messages' => serialize($this->messages),
      'timestamp' => time(),
      'sender_data' => serialize($this->senderData),
      'status' => 'initiated',
      'response' => FALSE,
    ]);
    $this->entity->save();
    return $this->entity->id();
  }

  /**
   * Send e-Boks message callback.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function send() {
    if (!$this->configValid) {
      return FALSE;
    }

    $sent_result = FALSE;
    foreach ($this->messages as $message) {

      $mpdf = new Mpdf(['tempDir' => drupal_realpath('temporary://mpdf')]);
      $mpdf->WriteHTML(render($message['content']));
      $filename = 'attachment.pdf';
      $attachament_content = $mpdf->Output($filename, 'S');
      $attachment = [
        'filecontent' => $attachament_content,
        'filename' => $filename,
        'filemime' => 'application/pdf'
      ];
      $params = [
        'headers' => [
          'Content-Type' => 'text/plain; charset=UTF-8;',
          'Content-Transfer-Encoding' => '8Bit',
        ],
        'from' => $this->config->get('from'),
        'subject' => $message['subject'],
        'body' => $this->getReceiver() . "\n" . $message['description'],
        'attachment' => $attachment,
      ];

      $langcode = $this->languageManager->getDefaultLanguage()->getId();
      if ($sent_result = $this->mailManager->mail('eboks', 'msoutlook', $this->config->get('to'), $langcode, $params)) {
        $this->entity->set('status', 'sent');
        $this->entity->save();
      }
      else {
        $this->logger->error('eBoks MSOutlook failed sending mail message to @to.', [
          '@to' => $this->config->get('to'),
        ]);
        break;
      }
    }

    return $sent_result;
  }

  /**
   * Validation get function.
   */
  public function isValid() {
    return $this->configValid;
  }

  /**
   * Send e-Boks message callback.
   */
  private function getReceiver() {
    $receiver = [
      'CPR' => '##DKALCPR=' . $this->receiverId . '##',
      'CVR' => '##DKALCVR=' . $this->receiverId . '##',
    ];
    return isset($receiver[$this->receiverType]) ? $receiver[$this->receiverType] : [];
  }

  /**
   * Configuration validate function.
   */
  private function validateConfig() {
    $required = [
      'from',
      'to',
    ];
    foreach ($required as $value) {
      if (empty($this->config->get($value))) {
        $this->configValid = FALSE;
        // Logging an error of validation.
        $this->logger->error('eBoks MSOutlook sender configuration is not valid. Key @name is missing.', [
          '@name' => $value,
        ]);
        return;
      }
    }
  }

}
