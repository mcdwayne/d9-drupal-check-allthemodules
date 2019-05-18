<?php

namespace Drupal\authorization_code_login_process_test\Plugin\CodeSender;

use Drupal\authorization_code\CodeSenderInterface;
use Drupal\authorization_code\Plugin\CodeSender\CodeSenderBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A code sender implementation that does not send the code.
 *
 * @CodeSender(
 *   id = "watchdog",
 *   title = @Translation("Watchdog")
 * )
 */
class Watchdog extends CodeSenderBase implements CodeSenderInterface, ContainerFactoryPluginInterface {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.authorization_code')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function sendCode(UserInterface $user, string $code) {
    $this->logger->debug('Code: @code', ['@code' => $code]);
  }

}
