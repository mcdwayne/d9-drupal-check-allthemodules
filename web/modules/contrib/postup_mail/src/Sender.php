<?php

namespace Drupal\postup_mail;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class Sender implements SenderInterface {
  use StringTranslationTrait;

  /**
   * The guzzle http.
   *
   * @var \GuzzleHttp\Client
   */
  protected $guzzleHttp;

  /**
   * The contact settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config settings for postup.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configSettings;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a Sender object.
   *
   * @param \GuzzleHttp\Client $guzzle_http
   *   The guzzle http.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(Client $guzzle_http, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->guzzleHttp = $guzzle_http;
    $this->configFactory = $config_factory;
    $this->configSettings = $this->configFactory->get('postup_mail.settings');
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function send_mail_template($email, $content = FALSE) {
    $options = [
      'sendTemplateId' => $this->configSettings->get('template_id'),
      'recipients' => [
        [
          'address' => $email,
          'externalId' => $this->configSettings->get('prefix') . $email,
        ],
      ],
    ];
    if ($content) {
      $options['content'] = $content;
    }
    $response = $this->send('templatemailing', $options);
    if ($response->status == 'DONE') {
      drupal_set_message($this->t($this->configSettings->get('message_success_text')));
    } else {
      drupal_set_message($this->t($this->configSettings->get('message_error_text')));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addRecipientToList($listId, $recipientId, $status = 'NORMAL') {
    $options = [
      'listId' => $listId,
      'recipientId' => $recipientId,
      'status' => $status,
    ];
    $this->send('listsubscription', $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getList($listId) {
    $options = [
      'listId' => $listId,
    ];
    $list = $this->send('list', $options);
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function addRecipient($email) {
    $options = [
      'address' => $email,
      'externalId' => $this->configSettings->get('prefix') . $email,
      'status' => 'N',
      'channel' => 'E',
    ];
    $recipient = $this->send('recipient', $options);
    return $recipient->recipientId;
  }

  /**
   * {@inheritdoc}
   */
  public function send($type, $options = []) {
    $content = FALSE;
    try {
      $url = $this->configSettings->get('url') . '/' . $type;
      $response = $this->guzzleHttp->post($url, [
        'json' => $options,
        'auth' => [
          $this->configSettings->get('login'),
          $this->configSettings->get('password')
        ],
      ]);
      $content = \GuzzleHttp\json_decode($response->getBody()->getContents());
      if ($this->configSettings->get('logging')) {
        $this->loggerFactory->get('postup_mail')->info(print_r($content, TRUE));
      }
    } catch (RequestException $e) {
      $this->loggerFactory->get('postup_mail')->error($e->getMessage());
    }
    return $content;
  }

}
