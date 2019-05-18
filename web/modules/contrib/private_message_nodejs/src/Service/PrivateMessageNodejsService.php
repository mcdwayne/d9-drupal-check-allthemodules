<?php

namespace Drupal\private_message_nodejs\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\private_message\Entity\PrivateMessageInterface;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;

/**
 * Provides services for the PrivateMessageNodejs module.
 */
class PrivateMessageNodejsService implements PrivateMessageNodejsServiceInterface {

  use StringTranslationTrait;
  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The log service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a PrivateMessageNodejsService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The log service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    Token $token,
    LoggerChannelFactoryInterface $logger
  ) {
    $this->configFactory = $configFactory;
    $this->token = $token;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function attachNodeJsLibary($library, array &$element) {

    static $attached = FALSE;

    $config = $this->configFactory->get('private_message_nodejs.settings');
    // Attach the library to handle Node.js integration.
    $element['#attached']['library'][] = 'private_message_nodejs/' . $library;
    // Set the URL to the backend Node.js app.
    $element['#attached']['drupalSettings']['privateMessageNodejs']['nodejsUrl'] = $config->get('nodejs_url');
    // Set the secret for the backend Node.js app.
    $element['#attached']['drupalSettings']['privateMessageNodejs']['nodejsSecret'] = $config->get('nodejs_secret');
    // Enable/disable JavaScript debugging.
    $element['#attached']['drupalSettings']['privateMessageNodejs']['debugEnabled'] = $config->get('enable_debug');

    // The following only needs to happen once per request.
    if (!$attached) {
      $attached = TRUE;
      if ($config->get('enable_debug')) {
        $this->logger->get('Private Message NodeJS debug: connection')
          ->notice($this->t(
            'Node.js connection info. Node.js URL: @url, Node.js secret: @secret',
            [
              '@url' => $config->get('nodejs_url'),
              '@secret' => $config->get('nodejs_secret'),
            ]
          )
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBrowserPushNotificationData(
    PrivateMessageInterface $privateMessage,
    PrivateMessageThreadInterface $privateMessageThread
  ) {
    $config = $this->configFactory->get('private_message_nodejs.settings');

    $data = [
      'private_message' => $privateMessage,
      'private_message_thread' => $privateMessageThread,
    ];

    $message = [];

    $message['title'] = $this->token->replace(
      $config->get('browser_notification_title'),
      $data
    );

    $message['body'] = $this->token->replace(
      $config->get('browser_notification_body'),
      $data
    );

    $icon = $config->get('browser_notification_icon');
    if (!empty($icon)) {
      $message['icon'] = file_create_url(
        $this->token->replace(
          $icon,
          $data
        )
      );
    }

    $link = $config->get('browser_notification_link');
    if (!empty($link)) {
      $message['link'] = $this->token->replace(
        $link,
        $data
      );
    }

    return $message;
  }

}
