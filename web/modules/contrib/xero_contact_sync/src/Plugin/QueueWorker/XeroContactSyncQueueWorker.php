<?php

namespace Drupal\xero_contact_sync\Plugin\QueueWorker;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\xero_contact_sync\XeroContactSyncRemoteUserMatcher;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Xero contact sync contact creation worker.
 *
 * @QueueWorker(
 *   id = "xero_contact_sync_create",
 *   title = @Translation("Xero Contact Sync Contact Creation"),
 *   cron = {"time" = 60}
 * )
 */
class XeroContactSyncQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A user matcher service.
   *
   * @var \Drupal\xero_contact_sync\XeroContactSyncRemoteUserMatcher
   */
  protected $userMatcher;

  /**
   * {@inheritdoc}
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\xero_contact_sync\XeroContactSyncRemoteUserMatcher $user_matcher
   *   The Xero user matcher service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, XeroContactSyncRemoteUserMatcher $user_matcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->userMatcher = $user_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.xero_contact_sync'),
      $container->get('entity_type.manager'),
      $container->get('xero_contact_sync.remote_user_matcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $user_id = $data['user_id'];

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($user_id);
    if ($user !== NULL) {
      if ($user->get('xero_contact_id')->value !== NULL) {
        return;
      }
      $this->userMatcher->matchRemoteUser($user);
    }
    else {
      $this->logger->log(LogLevel::WARNING, (string) new FormattableMarkup('There was no user with user id @user_id for creating remotely.', [
        '@user_id' => $user_id,
      ]));
    }
  }

}
