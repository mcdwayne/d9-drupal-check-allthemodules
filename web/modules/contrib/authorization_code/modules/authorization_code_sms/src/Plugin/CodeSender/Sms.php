<?php

namespace Drupal\authorization_code_sms\Plugin\CodeSender;

use Drupal\authorization_code\Exceptions\FailedToSendCodeException;
use Drupal\authorization_code\Plugin\CodeSender\CodeSenderBase;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Exception\NoPhoneNumberException;
use Drupal\sms\Exception\SmsPluginReportException;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Provider\PhoneNumberProviderInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sms implementation of the code sender plugin.
 *
 * @CodeSender(
 *   id = "sms",
 *   title = @Translation("SMS")
 * )
 */
class Sms extends CodeSenderBase implements ContainerFactoryPluginInterface, DependentPluginInterface {

  /**
   * The fallback gateway id (or null if no fallback is defined).
   *
   * @var string|null
   */
  protected $fallbackGatewayId;

  /**
   * Associative array of gateway options (gateway_id => label).
   *
   * @var string[]
   */
  protected $gatewayOptions;

  /**
   * A logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Token parser service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  private $token;

  /**
   * Phone number provider service.
   *
   * @var \Drupal\sms\Provider\PhoneNumberProviderInterface
   */
  private $phoneNumberProvider;

  /**
   * Sms constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Utility\Token $token
   *   Token parser service.
   * @param \Drupal\sms\Provider\PhoneNumberProviderInterface $phone_number_provider
   *   Phone number provider service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger.
   * @param \Drupal\Core\Config\ImmutableConfig $sms_settings
   *   The SMS settings configuration object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $gateway_storage
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, Token $token, PhoneNumberProviderInterface $phone_number_provider, LoggerInterface $logger, ImmutableConfig $sms_settings, EntityStorageInterface $gateway_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->phoneNumberProvider = $phone_number_provider;
    $this->token = $token;
    $this->logger = $logger;
    $this->fallbackGatewayId = $sms_settings->get('fallback_gateway');
    $this->gatewayOptions = array_map(function (SmsGateway $gateway) {
      return $gateway->label();
    }, $gateway_storage->loadMultiple());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token'),
      $container->get('sms.phone_number'),
      $container->get('logger.channel.authorization_code'),
      $container->get('config.factory')->get('sms.settings'),
      $container->get('entity_type.manager')->getStorage('sms_gateway')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return NestedArray::mergeDeep(parent::defaultConfiguration(), [
      'settings' => [
        'gateway' => $this->fallbackGatewayId,
      ],
    ]);
  }

  /**
   * The SMS Gateway ID.
   *
   * @return string|null
   *   The SMS gateway ID, or null if no SMS gateway was configured.
   */
  protected function smsGatewayId() {
    return NestedArray::getValue(
      $this->configuration,
      ['settings', 'gateway']
    );
  }

  /**
   * The SMS Gateway.
   *
   * @return \Drupal\sms\Entity\SmsGateway|null
   *   The SMS gateway, or null if no SMS gateway was configured.
   */
  protected function smsGateway() {
    $gateway_id = $this->smsGatewayId();
    return $gateway_id ? SmsGateway::load($gateway_id) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function sendCode(UserInterface $user, string $code) {
    try {
      $this->phoneNumberProvider->sendMessage($user, $this->buildSmsMessage($user, $code));
    }
    catch (SmsPluginReportException $e) {
      $this->logger->error('Failed to send code.<br> Message: %message<br> Trace: <pre>@trace</pre>', [
        '%message' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);
      throw new FailedToSendCodeException($user, $e);
    }
    catch (NoPhoneNumberException $e) {
      $this->logger->error('No phone number found for @name (%id)', [
        '@name' => $user->getDisplayName(),
        '%id' => $user->id(),
      ]);
      throw new FailedToSendCodeException($user, $e);
    }
  }

  /**
   * Builds the SMS message object to be sent to the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $code
   *   The authorization code.
   *
   * @return \Drupal\sms\Message\SmsMessage
   *   The SMS message.
   *
   * @see \Drupal\authorization_code_sms\Plugin\CodeSender\Sms::buildMessage
   */
  protected function buildSmsMessage(UserInterface $user, string $code): SmsMessage {
    $message = new SmsMessage();
    $message->setMessage($this->buildMessage($user, $code));
    if ($gateway = $this->smsGateway()) {
      $message->setGateway($gateway);
    }
    return $message;
  }

  /**
   * Builds the message contents to be sent to the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $code
   *   The authorization code.
   *
   * @return string
   *   The message.
   */
  private function buildMessage(UserInterface $user, string $code): string {
    return $this->token->replace($this->messageTemplate(), [
      'user' => $user,
      'authorization_code' => $code,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['gateway'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Gateway'),
      '#description' => $this->t('The SMS gateway to use.'),
      '#options' => $this->gatewayOptions,
      '#default_value' => $this->smsGatewayId(),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return NestedArray::mergeDeep(parent::calculateDependencies(), [
      'plugin' => ['sms.phone.user.user'],
      'module' => ['authorization_code_sms', 'sms'],
    ]);
  }

}
