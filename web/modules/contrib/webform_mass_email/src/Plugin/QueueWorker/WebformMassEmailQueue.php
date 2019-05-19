<?php

namespace Drupal\webform_mass_email\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Utility\Token;
use Drupal\webform\Entity\WebformSubmission;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send the pending webform mass emails for the users.
 *
 * @QueueWorker(
 *   id = "webform_mass_email",
 *   title = @Translation("Webform Mass Email queue"),
 *   cron = {"time" = 15}
 * )
 */
class WebformMassEmailQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new WebformMassEmailQueue instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager, Token $token, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->token = $token;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('plugin.manager.mail'),
      $container->get('token'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $config = $this->configFactory->get('webform_mass_email.settings');
    $html = $config->get('html');
    $log = $config->get('log');

    // Basic validation of item.
    if (empty($data['email']) || empty($data['subject']) || empty($data['body'])) {
      // Notify user when not enough values given.
      if ($log) {
        $this->loggerFactory->get('webform_mass_email')
          ->error('Sending email for user at webform submission @id failed. Not enough values given.', [
            '@id' => $data['id'],
          ]);
      }
      // Delete faulty item so that it doesn't get re-queued.
      return;
    }
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $token_data = ['webform_submission' => WebformSubmission::load($data['id'])];
    $token_options = ['langcode' => $langcode, 'clear' => TRUE];

    // Build the message array for sending.
    $params = [
      'subject' => $data['subject'],
      'body' => $this->token->replace($data['body'], $token_data, $token_options),
      'html' => $html,
    ];

    // Do the sending.
    $result = $this->mailManager->mail('webform_mass_email', 'mass_email', $data['email'], $langcode, $params, NULL, TRUE);

    if ($result['result'] === TRUE) {
      // Log if set on admin page.
      if ($log) {
        $this->loggerFactory->get('webform_mass_email')
          ->info('Successfully sent email from webform "%webform". Submission ID: "@id", Subject: "%subject", Address: "%email".', [
            '%webform' => $data['webform_title'] . ' (ID: ' . $data['webform_id'] . ')',
            '@id' => $data['id'],
            '%subject' => $data['subject'],
            '%email' => $data['email'],
          ]);
      }
      // Success, delete the item.
      return;
    }
    // Something went wrong (server error or something).
    else {
      // Throw exception so the item will get re-queued.
      throw new Exception('Sending email for user at webform submission ' . $data['id'] . ' failed.');
    }
  }

}
